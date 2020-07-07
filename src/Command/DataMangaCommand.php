<?php

namespace App\Command;

// Permet d'augmenter la mémoire ram alloué pour ce script seulement
//! Possible d'optimiser le code ( commande avec argument ??? )
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
            ->setDescription('Commande scraping manga data from jiken.moe API we want to exclude')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    { 
        $io = new SymfonyStyle($input, $output);

        // Chemin du fichier
        $file = __DIR__ .'/../../public/assets/json/manga-genre_exclude.json';

        // Je récupère le contenu
        $current = file_get_contents($file);

        // Je le décode afin d'avoir un tableau associatif
        $currentDecode = json_decode($current, true);

        // Cette variable me permettra de stocké tout les manga que je veut stocké en bdd
        $mangas = [];
        // Cette variable me permet d'avoir un feedback pour la sauvegarde
        $nbMangaTotal = 0;
        foreach ($currentDecode as $genre) {

            // La page courante
            $page = 1;
            // Le nombre de manga totaux scrapé ( sert au feedback )
            $nbManga = 0;
            // L'id du genre scrapé
            $genre = $genre['id'];

            while(true) {
                
                // Requete vers l'api
                $response = $this->client->request('GET', 'https://api.jikan.moe/v3/search/manga?genre=' . $genre . '&page='. $page .'');
                // Je ralenti l'éxécution du code de 2 seconde afin d'éviter l'erreur 429
                sleep(3);
                // Je récupère le contenu
                $content = $response->getContent();
                $content = $response->toArray();
                
                // Condition qui permet de cassé la boucle while pour passer au genre suivant
                if (empty($content['results'])) {
                    $io->success('Le genre n°'.$genre.' est complet, ' . ($page - 1) .' pages on été scrapé et stocké dans un tableau');
                    $io->success( $nbManga . ' mangas ont été rajouté pour le genre n°'.$genre);

                    break;
                }
                
                // Boucle pour crée les objets ExcludeManga
                foreach ($content['results'] as $data)
                {
                    // Permet l'ajout de manga qui n'existent pas en bdd
                    if ($this->excludeMangaRepository->findByMalId($data['mal_id']) != null)
                    {
                        continue;
                    }
                    
                    $manga = new ExcludeManga();
                    $manga->setName($data['title']);
                    $manga->setMalId($data['mal_id']);
                    $manga->setPage($page);
                    $manga->setGenreId($genre);

                    // Je push dans le tableau $mangas
                    $mangas[] = $manga;
                    // je met a null ma variable afin d'économiser de la ram
                    $manga = null;
                    // j'incrémente mes deux variable de feedback
                    $nbManga++;
                    $nbMangaTotal++;
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

        $nbManga = 1;
        foreach ($mangas as $manga) {
                
            // persist consomment pas mal de mémoire ram
            $this->em->persist($manga);

            // je met a null ma variable afin d'économiser de la ram
            $manga = null;

            // FeedBack
            echo $nbManga . " / " . $nbMangaTotal . " sauvegardé" . PHP_EOL;
            $nbManga++;
        }

        // flush consomment énormément de mémoire ram
        $this->em->flush();

        // Ram feedback
        $this->memoryUsed();
        
        $io->success( $nbMangaTotal . ' mangas exclus ont été rajouté en bdd');

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
