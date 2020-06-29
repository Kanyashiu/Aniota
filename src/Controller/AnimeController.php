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
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?order_by=title&rated=g');
        //! Pour gérer l'erreur 429 plus tard
        //! Erreur à gerer les refresh intempestif
        $statusCode = $response->getStatusCode();
        $content = $response->toArray();

        return $this->render('anime/index.html.twig', [
            'animes' => $content['results'],
            'page' => 1,
        ]);
    }
}
