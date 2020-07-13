<?php

namespace App\Command;

// Allows to increase the allocated RAM for this script only
ini_set('memory_limit','256M');

use App\Entity\ExcludeAnime;
use App\Repository\ExcludeAnimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataAnimeCommand extends Command
{
    protected static $defaultName = 'data:anime';

    private $client;
    private $em;
    private $excludeAnimeRepository;

    public function __construct( HttpClientInterface $httpClient, EntityManagerInterface $em, ExcludeAnimeRepository $excludeAnimeRepository)
    {
        parent::__construct();
        $this->client = $httpClient;
        $this->em = $em;
        $this->excludeAnimeRepository = $excludeAnimeRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Command scraping anime data from jiken.moe API we want to exclude')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    { 
        $io = new SymfonyStyle($input, $output);

        // Path of the file containing the ids of the manga to exclude (based on the ids of the jikan api)
        $file = __DIR__ .'/../../public/assets/json/anime-genre_exclude.json';

        // I retrieve the contents of the file
        $current = file_get_contents($file);

        // I decode the file in order to have an array
        $currentDecode = json_decode($current, true);

        // This variable will allow me to store all the manga I want to store in database
        $animes = [];
        
        // This variable allows me to get feedback in the command terminal
        $nbAnimeTotal = 0;
        foreach ($currentDecode as $genre) {

            // Actual page i want to scrap
            $page = 1;
            // The number of total anime scrapped ( used for feedback )
            $nbAnime = 0;
            // The id of the anime
            $genre = $genre['id'];

            while(true) {
                
                // Request to the jikan api
                $response = $this->client->request('GET', 'https://api.jikan.moe/v3/search/anime?genre=' . $genre . '&page='. $page .'');
                // I slow down the code execution by 2 seconds to avoid error 429 from the api
                sleep(3);
                // I'm retrieving the contents
                $content = $response->getContent();
                $content = $response->toArray();
                
                // Condition that allows you to break the while loop to go to the next anime genre
                if (empty($content['results'])) {
                    $io->success('Le genre n°'.$genre.' est complet, ' . ($page - 1) .' pages on été scrapé et stocké dans un tableau');
                    $io->success( $nbAnime . ' anime ont été rajouté pour le genre n°'.$genre);

                    break;
                }
                
                // Loop for creating ExcludeAnime objects
                foreach ($content['results'] as $data)
                {
                    // Allows the addition of anime that do not exist in bdd
                    if ($this->excludeAnimeRepository->findByMalId($data['mal_id']) != null)
                    {
                        continue;
                    }

                    // Creation of ExcludeAnime objects and setting his properties
                    $anime = new ExcludeAnime();
                    $anime->setName($data['title']);
                    $anime->setMalId($data['mal_id']);
                    $anime->setPage($page);
                    $anime->setGenreId($genre);

                    // I push in the $animes array
                    $animes[] = $anime;
                    // I set my variable to null in order to save RAM
                    $anime = null;
                    // I increment my two feedback variables
                    $nbAnime++;
                    $nbAnimeTotal++;
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

        $nbAnime = 1;
        foreach ($animes as $anime) {
                
            // Persisting my entities
            $this->em->persist($anime);

            // I set my variable to null in order to save RAM
            $anime = null;

            // Used for feedback
            echo $nbAnime . " / " . $nbAnimeTotal . " sauvegardé" . PHP_EOL;
            $nbAnime++;
        }

        // Save all my entities in database
        $this->em->flush();

        // Ram feedback
        $this->memoryUsed();
        
        // Success message when my command is over
        $io->success( $nbAnimeTotal . ' animes exclus ont été rajouté en bdd');

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
