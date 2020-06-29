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
        $page = $request->query->get('page');
        //dd($page);

        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }

        if (isset($_GET['genre']) && !empty($_GET['genre'])) {
            $genre = $_GET['genre'];
        }
        else {
            $genre = '0';
        }

        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?order_by=title&type=manga&page=' . $page . '&genre=' . $genre . '');
        //dd($response);

        $content = $response->getContent();
        $content = $response->toArray();

        $results = $content['results'];
        //dd($results);

        return $this->render('manga/index.html.twig', [
            'mangas' => $results,
            'page' => $page,
        ]);
    }
}
