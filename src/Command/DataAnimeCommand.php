<?php

namespace App\Command;

// Permet d'augmenter la mémoire ram alloué pour ce script seulement
//! Possible d'optimiser le code ( commande avec argument ??? )
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
            ->setDescription('Commande scraping anime data from jiken.moe API we want to exclude')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    { 
        $io = new SymfonyStyle($input, $output);

        // Chemin du fichier
        $file = __DIR__ .'/../../public/assets/json/anime-genre_exclude.json';

        // Je récupère le contenu
        $current = file_get_contents($file);

        // Je le décode afin d'avoir un tableau associatif
        $currentDecode = json_decode($current, true);

        // Cette variable me permettra de stocké tout les animes que je veut stocké en bdd
        $animes = [];
        // Cette variable me permet d'avoir un feedback pour la sauvegarde
        $nbAnimeTotal = 0;
        foreach ($currentDecode as $genre) {

            // La page courante
            $page = 1;
            // Le nombre d'anime totaux scrapé ( sert au feedback )
            $nbAnime = 0;
            // L'id du genre scrapé
            $genre = $genre['id'];

            while(true) {
                
                // Requete vers l'api
                $response = $this->client->request('GET', 'https://api.jikan.moe/v3/search/anime?genre=' . $genre . '&page='. $page .'');
                // Je ralenti l'éxécution du code de 2 seconde afin d'éviter l'erreur 429
                sleep(3);
                // Je récupère le contenu
                $content = $response->getContent();
                $content = $response->toArray();
                
                // Condition qui permet de cassé la boucle while pour passer au genre suivant
                if (empty($content['results'])) {
                    $io->success('Le genre n°'.$genre.' est complet, ' . ($page - 1) .' pages on été scrapé et stocké dans un tableau');
                    $io->success( $nbAnime . ' anime ont été rajouté pour le genre n°'.$genre);

                    break;
                }
                
                // Boucle pour crée les objets ExcludeAnime
                foreach ($content['results'] as $data)
                {
                    // Permet l'ajout de manga qui n'existent pas en bdd
                    if ($this->excludeAnimeRepository->findByMalId($data['mal_id']) != null)
                    {
                        continue;
                    }

                    $anime = new ExcludeAnime();
                    $anime->setName($data['title']);
                    $anime->setMalId($data['mal_id']);
                    $anime->setPage($page);
                    $anime->setGenreId($genre);

                    // Je push dans le tableau $animes
                    $animes[] = $anime;
                    // je met a null ma variable afin d'économiser de la ram
                    $anime = null;
                    // j'incrémente mes deux variable de feedback
                    $nbAnime++;
                    $nbAnimeTotal++;
                }
                
                // Je met a null mes variable afin d'économiser de la ram
                $response = null;
                $content = null;
                
                // Feedback
                echo 'La page numéro '. $page .' du genre n°' .$genre. ' à bien été lu'. PHP_EOL;
                // Incrémentation de $page pour passer à la page suivante à scrapé
                $page++;

                // Ram feedback
                //$this->memoryUsed();
            }

        }

        $nbAnime = 1;
        foreach ($animes as $anime) {
                
            // persist consomment pas mal de mémoire ram
            $this->em->persist($anime);

            // je met a null ma variable afin d'économiser de la ram
            $anime = null;

            // FeedBack
            echo $nbAnime . " / " . $nbAnimeTotal . " sauvegardé" . PHP_EOL;
            $nbAnime++;
        }

        // flush consomment énormément de mémoire ram
        $this->em->flush();

        // Ram feedback
        $this->memoryUsed();
        
        $io->success( $nbAnimeTotal . ' animes exclus ont été rajouté en bdd');

        return 0;
    }

    public function memoryUsed()
    {
        //https://stackoverflow.com/questions/10544779/how-can-i-clear-the-memory-while-running-a-long-php-script-tried-unset
        //https://stackoverflow.com/questions/584960/whats-better-at-freeing-memory-with-php-unset-or-var-null
        //https://stackoverflow.com/questions/2461762/force-freeing-memory-in-php
        //https://stackoverflow.com/questions/46799995/php-how-to-clear-memory-in-a-long-loop
        //https://stackoverflow.com/questions/18122369/how-to-free-memory-after-array-in-php-unset-and-null-seems-to-not-working
        //https://browse-tutorials.com/tutorial/php-memory-management
        //! TEST RAM
        echo 'Usage: ' . (memory_get_usage(true) / 1024 / 1024) . ' MB' . PHP_EOL;
        echo 'Peak: ' . (memory_get_peak_usage(true) / 1024 / 1024) . ' MB' . PHP_EOL;
        echo 'Memory limit: ' . str_replace('M', ' MB', ini_get('memory_limit')) . PHP_EOL;
        //! TEST RAM
        
        //! Piste possible d'optimisation
        //https://davidwalsh.name/increase-php-memory-limit-ini_set ( ini_set('memory_limit','16M'); ) <= Pas sur pour celle là
        //https://www.php.net/manual/fr/book.info.php
        //https://www.thegeekstuff.com/2014/04/optimize-php-code/
        //https://blog.nicolashachet.com/developpement-php/optimiser-les-performances-de-son-code-php/
    }
}
