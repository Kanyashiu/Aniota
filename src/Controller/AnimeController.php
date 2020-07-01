<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;

class AnimeController extends AbstractController
{
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
            
            //! A mettre dans un service
            // Vérification pour supprimer des notation ( controle plus fin )
            $count = 0;
            foreach ( $animeRating as $data ) {
                $result = array_search($rating, $data);
                
                $count++;

                if ( $result != false ) {
                    // Le break permet d'éviter que le if plus bas se déclenche
                    break;
                }

                if ( count($animeRating) == $count )
                {
                    throw new \Exception('Erreur 404 Rating', 404);
                }
            }
            //!============================

            $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?rated=' . $rating . '&page=' . $page . '');
        }
        else if (isset( $_GET['genre']) && !empty( $_GET['genre'])) {
            
            $genre = $_GET['genre'];

            //! A mettre dans un service
            // Vérification pour supprimer des notation ( controle plus fin )
            $count = 0;
            foreach ( $animeGenre as $data ) {
                $result = array_search($genre, $data);
                
                $count++;

                if ( $result != false ) {
                    // Le break permet d'éviter que le if plus bas se déclenche
                    break;
                }

                if ( count($animeGenre) == $count )
                {
                    throw new \Exception('Erreur 404 Rating', 404);
                }
            }
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
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.jikan.moe/v3/anime/' . $id . '');

        $anime = $response->toArray();

        //! Condition pour rejeter certain manga if + throw ?
        //! Idée possible récuperer l'id des manga que l'ont ne veut pas et les mettre dans un fichier pour procéder à un controle service YouShallNotPass ?

        return $this->render('anime/details.html.twig', [
            'anime' => $anime
        ]);
    }

}
