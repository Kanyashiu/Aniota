<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MangaController extends AbstractController
{
    /**
     * @Route("/manga", name="manga")
     */
    public function browse(HttpClientInterface $httpClient, Request $request)
    {
        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?order_by=title&type=manga');
        //dd($response);

        $content = $response->getContent();
        $content = $response->toArray();

        $results = $content['results'];
        //dd($results);

        $page = $request->query->get('page');
        //dd($page);

        return $this->render('manga/index.html.twig', [
            'mangas' => $results,
            'page' => $page
        ]);
    }
}
