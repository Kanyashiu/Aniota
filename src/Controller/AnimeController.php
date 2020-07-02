<?php

namespace App\Controller;

use App\Services\YouShallNotPass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;

class AnimeController extends AbstractController
{
    private $youShallNotPass;

    public function __construct(YouShallNotPass $youShallNotPass)
    {
        $this->youShallNotPass = $youShallNotPass;
    }
    /**
     * @Route("/anime", name="anime", methods={"GET"})
     */
    public function browse()
    {
        $client = HttpClient::create();
        $animeGenre = json_decode(file_get_contents("assets/json/anime-genre.json"), true);
        $animeRating = json_decode(file_get_contents("assets/json/anime-rating.json"), true);
        
        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }
        
        if (isset( $_GET['rated']) && !empty( $_GET['rated'])) {
            $rating = $_GET['rated'];
            
            //! Service
            $this->youShallNotPass->typeControlBrowse($animeRating, $rating);
            //!============================

            $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?rated=' . $rating . '&page=' . $page . '');
        }
        else if (isset( $_GET['genre']) && !empty( $_GET['genre'])) {
            
            $genre = $_GET['genre'];

            //! Service
            $this->youShallNotPass->typeControlBrowse($animeGenre, $genre);
            //!============================
            
            $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?genre=' . $genre . '&page=' . $page . '');
        }
        else {
            $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?order_by=title');
        }

        // //! Pour gérer l'erreur 429 plus tard
        // //! Erreur à gerer les refresh intempestif
        // $statusCode = $response->getStatusCode();
        
        $animes = $response->toArray();

        //! Service
        $animes = $this->youShallNotPass->contentControlBrowseAnime($animes);
        //!============================

        return $this->render('anime/index.html.twig', [
            'animes' => $animes['results'],
            'page' => $page,
            'rated' => $rating ?? null,
            'genre' => $genre ?? null,
            'animeGenre' => $animeGenre,
            'animeRating' => $animeRating,
        ]);
    }

    /**
     * @Route("/anime/{id}", name="anime_details", requirements={"id": "\d+"}, methods={"GET"})
     */
    public function details($id)
    {
        //! Service
        // Ce code permet d'éviter les spam qui provoquent l'erreur 429
        touch('assets/json/anime-YSNP.json');
        $animeYSNP = json_decode(file_get_contents("assets/json/anime-YSNP.json"), true);
        $this->youShallNotPass->contentControlExistingDataAnime($animeYSNP, $id);
        //! ========

        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.jikan.moe/v3/anime/' . $id . '');

        $anime = $response->toArray();

        //! Service
        $anime = $this->youShallNotPass->contentControlDetailsAnime($anime);
        //! ======

        return $this->render('anime/details.html.twig', [
            'anime' => $anime
        ]);
    }

}
