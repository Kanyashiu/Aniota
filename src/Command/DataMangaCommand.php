<?php

namespace App\Command;

// Allows to increase the allocated RAM for this script only
ini_set('memory_limit','256M');

use App\Entity\ExcludeManga;
use App\Repository\ExcludeMangaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataMangaCommand extends Command
{
    protected static $defaultName = 'data:manga';

    private $client;
    private $em;
    private $excludeMangaRepository;

    public function __construct( HttpClientInterface $httpClient, EntityManagerInterface $em, ExcludeMangaRepository $excludeMangaRepository)
    {
        parent::__construct();
        $this->client = $httpClient;
        $this->em = $em;
        $this->excludeMangaRepository = $excludeMangaRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Command scraping manga data from jiken.moe API we want to exclude')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    { 
        $io = new SymfonyStyle($input, $output);

        // Path of the file containing the ids of the manga to exclude (based on the ids of the jikan api)
        $file = __DIR__ .'/../../public/assets/json/manga-genre_exclude.json';

        // I retrieve the contents of the file
        $current = file_get_contents($file);

        // I decode the file in order to have an array
        $currentDecode = json_decode($current, true);

        // This variable will allow me to store all the manga I want to store in database
        $mangas = [];

        // This variable allows me to get feedback in the command terminal
        $nbMangaTotal = 0;
        foreach ($currentDecode as $genre) {

            // Actual page i want to scrap
            $page = 1;
            // The number of total manga scrapped ( used for feedback )
            $nbManga = 0;
            // The id of the manga
            $genre = $genre['id'];

            while(true) {
                
                // Request to the jikan api
                $response = $this->client->request('GET', 'https://api.jikan.moe/v3/search/manga?genre=' . $genre . '&page='. $page .'');
                // I slow down the code execution by 2 seconds to avoid error 429 from the api
                sleep(3);
                // I'm retrieving the contents
                $content = $response->getContent();
                $content = $response->toArray();
                
                // Condition that allows you to break the while loop to go to the next manga genre
                if (empty($content['results'])) {
                    $io->success('Le genre n°'.$genre.' est complet, ' . ($page - 1) .' pages on été scrapé et stocké dans un tableau');
                    $io->success( $nbManga . ' mangas ont été rajouté pour le genre n°'.$genre);

                    break;
                }
                
                // Loop for creating ExcludeManga objects
                foreach ($content['results'] as $data)
                {
                    // Allows the addition of manga that do not exist in bdd
                    if ($this->excludeMangaRepository->findByMalId($data['mal_id']) != null)
                    {
                        continue;
                    }
                    
                    // Creation of ExcludeManga objects and setting his properties
                    $manga = new ExcludeManga();
                    $manga->setName($data['title']);
                    $manga->setMalId($data['mal_id']);
                    $manga->setPage($page);
                    $manga->setGenreId($genre);

                    // I push in the $mangas array
                    $mangas[] = $manga;
                    // I set my variable to null in order to save RAM
                    $manga = null;
                    // I increment my two feedback variables
                    $nbManga++;
                    $nbMangaTotal++;
                }
                
                // I set my variable to null in order to save RAM
                $response = null;
                $content = null;
                
                // Used for feedback
                echo 'La page numéro '. $page .' du genre n°' .$genre. ' à bien été lu'. PHP_EOL;
                // Incrementing the variable $page to move to the next page of the api
                $page++;

                // Ram feedback
                //$this->memoryUsed();
            }

        }

        $nbManga = 1;
        foreach ($mangas as $manga) {
                
            // Persisting my entities
            $this->em->persist($manga);

            // I set my variable to null in order to save RAM
            $manga = null;

            // Used for feedback
            echo $nbManga . " / " . $nbMangaTotal . " sauvegardé" . PHP_EOL;
            $nbManga++;
        }

        // Save all my entities in database
        $this->em->flush();

        // Ram feedback
        $this->memoryUsed();
        
        // Success message when my command is over
        $io->success( $nbMangaTotal . ' mangas exclus ont été rajouté en bdd');

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
