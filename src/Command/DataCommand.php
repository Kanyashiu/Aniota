<?php

namespace App\Command;

// Allows to increase the allocated RAM for this script only
ini_set('memory_limit','256M');

use App\Entity\ExcludeAnime;
use App\Entity\ExcludeManga;
use App\Repository\ExcludeAnimeRepository;
use App\Repository\ExcludeMangaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataCommand extends Command
{
    protected static $defaultName = 'data:command';

    private $client;
    private $em;
    private $excludeMangaRepository;
    private $excludeAnimeRepository;

    public function __construct( HttpClientInterface $httpClient, EntityManagerInterface $em, ExcludeMangaRepository $excludeMangaRepository,ExcludeAnimeRepository $excludeAnimeRepository)
    {
        parent::__construct();
        $this->client = $httpClient;
        $this->em = $em;
        $this->excludeAnimeRepository = $excludeAnimeRepository;
        $this->excludeMangaRepository = $excludeMangaRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Command scraping manga/anime data from jiken.moe API we want to exclude')
            ->addArgument('dataType', InputArgument::REQUIRED, 'type of data we want to scrap ( manga or anime)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    { 
        $io = new SymfonyStyle($input, $output);
        // TODO
        // comments
        $dataType = strtolower($input->getArgument('dataType'));

        // comments
        if ($dataType != 'anime' && $dataType != 'manga' ) {
            $io->error('Your argument need to be "manga" or "anime"');

            return 1;
        }

        // comments
        $excludeObject = 'App\Entity\Exclude' . ucfirst($dataType);

        // comments
        $excludeRepository = 'exclude' . ucfirst($dataType) . 'Repository';

        // TODO END
        // Path of the file containing the ids of the manga/anime to exclude (based on the ids of the jikan api)
        $file = __DIR__ .'/../../public/assets/json/' . $dataType . '-genre_exclude.json';

        // I retrieve the contents of the file
        $current = file_get_contents($file);

        // I decode the file in order to have an array
        $currentDecode = json_decode($current, true);

        // This variable will allow me to store all the manga/anime I want to store in database
        $entities = [];
        
        // This variable allows me to get feedback in the command terminal
        $nbTotal = 0;
        foreach ($currentDecode as $genre) {

            // Actual page i want to scrap
            $page = 1;
            // The number of total manga/anime scrapped ( used for feedback )
            $nb = 0;
            // The id of the manga/anime
            $genre = $genre['id'];

            while(true) {
                
                // Request to the jikan api
                $response = $this->client->request('GET', 'https://api.jikan.moe/v3/search/' . $dataType . '?genre=' . $genre . '&page='. $page .'');
                // I slow down the code execution by 2 seconds to avoid error 429 from the api
                sleep(3);
                
                // I'm retrieving the contents
                $content = $response->getContent();
                $content = $response->toArray();
                
                // Condition that allows you to break the while loop to go to the next manga/anime genre
                if (empty($content['results'])) {
                    $io->success('Le genre n°'.$genre.' est complet, ' . ($page - 1) .' pages on été scrapé et stocké dans un tableau');
                    $io->success( $nb . ' ' . $dataType . ' ont été rajouté pour le genre n°'.$genre);

                    break;
                }
                
                // Loop for creating ExcludeManga/ExcludeAnime objects
                foreach ($content['results'] as $data)
                {
                    
                    // Allows the addition of manga/anime that do not exist in bdd
                    if ($this->$excludeRepository->findByMalId($data['mal_id']) != null)
                    {
                        continue;
                    }

                    // Creation of ExcludeManga/ExcludeAnime objects and setting his properties
                    $entity = new $excludeObject();
                    $entity->setName($data['title']);
                    $entity->setMalId($data['mal_id']);
                    $entity->setPage($page);
                    $entity->setGenreId($genre);

                    // I push in the $entities array
                    $entities[] = $entity;
                    // I set my variable to null in order to save RAM
                    $entity = null;
                    // I increment my two feedback variables
                    $nb++;
                    $nbTotal++;
                }
                
                // I set my variable to null in order to save RAM
                $response = null;
                $content = null;
                
                // Used for feedback
                echo 'La page numéro '. $page .' du genre n°' .$genre. ' des ' . $dataType . ' à bien été lu'. PHP_EOL;
                // Incrementing the variable $page to move to the next page of the api
                $page++;

                // Ram feedback
                //$this->memoryUsed();
            }

        }

        $nb = 1;
        foreach ($entities as $entity) {
                
            // Persisting my entities
            $this->em->persist($entity);

            // I set my variable to null in order to save RAM
            $entity = null;

            // Used for feedback
            echo $nb . " / " . $nbTotal . " sauvegardé" . PHP_EOL;
            $nb++;
        }

        // Save all my entities in database
        $this->em->flush();

        // Ram feedback
        $this->memoryUsed();
        
        // Success message when my command is over
        $io->success( $nbTotal . ' ' . $dataType . 's exclus ont été rajouté en bdd');

        return 0;
    }

    /**
     * This method is used to have information about the RAM consuming
     */
    public function memoryUsed()
    {
        echo 'Usage: ' . (memory_get_usage(true) / 1024 / 1024) . ' MB' . PHP_EOL;
        echo 'Peak: ' . (memory_get_peak_usage(true) / 1024 / 1024) . ' MB' . PHP_EOL;
        echo 'Memory limit: ' . str_replace('M', ' MB', ini_get('memory_limit')) . PHP_EOL;
    }
}
