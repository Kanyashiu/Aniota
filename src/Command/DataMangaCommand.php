<?php

namespace App\Command;

use App\Entity\ExcludeManga;
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

    public function __construct( HttpClientInterface $httpClient, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->client = $httpClient;
        $this->em = $em;
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

        $file = __DIR__ .'/../../public/assets/json/manga-genre_exclude.json';

        $current = file_get_contents($file);

        $currentDecode = json_decode($current, true);

        foreach ($currentDecode as $genre) {

            $page = 1;
            $nbManga = 0;
            $genre = $genre['id'];

            while(true) {
                $response = $this->client->request('GET', 'https://api.jikan.moe/v3/search/manga?genre=' . $genre . '&page='. $page .'');
                sleep(2);
                $content = $response->getContent();
                $content = $response->toArray();
                
                if (empty($content['results'])) {
                    $io->success('Le genre n°'.$genre.' est complet, ' . ($page - 1) .' pages on été scrapé et stocké en bdd');
                    $io->success( $nbManga . ' on été rajouté pour le genre n°'.$genre);

                    break;
                }
    
                foreach ($content['results'] as $data)
                {
                    $manga = new ExcludeManga();
                    $manga->setName($data['title']);
                    $manga->setMalId($data['mal_id']);
                    $manga->setPage($page);
                    $manga->setGenreId($genre);

                    $this->em->persist($manga);

                    //https://stackoverflow.com/questions/10544779/how-can-i-clear-the-memory-while-running-a-long-php-script-tried-unset
                    //https://stackoverflow.com/questions/584960/whats-better-at-freeing-memory-with-php-unset-or-var-null
                    //https://browse-tutorials.com/tutorial/php-memory-management
                    unset($manga);

                    $nbManga++;
                }
                $this->em->flush();
                
                echo 'La page numéro '. $page .' du genre n°' .$genre. ' à bien été lu'. PHP_EOL;
                $page++;
            }
        }
        
        $io->success('Les mangas exclus on été ajouté en bdd');

        return 0;
    }
}
