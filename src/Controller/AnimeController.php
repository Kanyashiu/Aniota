<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;

class AnimeController extends AbstractController
{
    /**
     * @Route("/anime", name="anime")
     */
    public function browse()
    {

        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }
        
        if (isset( $_GET['rated']) && !empty( $_GET['rated'])) {
            $rating = $_GET['rated'];
        }
        else {
            $rating = 'g';
        }

        if (isset( $_GET['genre']) && !empty( $_GET['genre'])) {
            $genre = $_GET['genre'];
        }
        else {
            $genre = '0';
        }
        
        
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?order_by=title&rated=' . $rating . '&page=' . $page . '&genre=' . $genre . '');
        
        // //! Pour gérer l'erreur 429 plus tard
        // //! Erreur à gerer les refresh intempestif
        // $statusCode = $response->getStatusCode();
        
        $animes = $response->toArray();

        return $this->render('anime/index.html.twig', [
            'animes' => $animes['results'],
            'page' => $page,
            'rated' => $rating,
        ]);
    }
}
