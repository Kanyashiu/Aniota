<?php

namespace App\Controller;

use App\Services\YouShallNotPass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MainController extends AbstractController
{
    private $youShallNotPass;

    public function __construct(YouShallNotPass $youShallNotPass)
    {
        $this->youShallNotPass = $youShallNotPass;
    }

    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('main/home.html.twig');
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function profile()
    {
        return $this->render('main/profile.html.twig');
    }

    /**
     * @Route("search", name="search", methods={"POST"})
     */
    public function search(Request $request, HttpClientInterface $httpClient)
    {
        $data = ucwords(strtolower($request->request->get('search')));

        sleep(2);
        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?q='. $data .'&page=1');
        $content = $response->getContent();
        $content = $response->toArray();

        $mangas = $content['results'];

        $mangas = $this->youShallNotPass->contentControlBrowseManga($mangas);
        
        sleep(2);
        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/anime?q='. $data .'&page=1');
        $content = $response->getContent();
        $content = $response->toArray();

        $animes = $content['results'];

        $animes = $this->youShallNotPass->contentControlBrowseAnime($animes);

        foreach ($mangas as $index => $manga) {
            $resultTitle = strpos($manga['title'], $data);
            $resultSynopsis = strpos($manga['synopsis'], $data);
            
            if ($resultTitle === false && $resultSynopsis === false) {
                unset($mangas[$index]);
            }
        }

        foreach ($animes as $index => $anime) {
            $resultTitle = strpos($anime['title'], $data);
            $resultSynopsis = strpos($anime['synopsis'], $data);

            if ($resultTitle === false && $resultSynopsis === false) {
                unset($animes[$index]);
            }
        }

        $arrayMerge = array_merge($mangas, $animes);

        return $this->render('main/search.html.twig', [
            'results' => $arrayMerge,
            'page' => 1,
            'data' => $data
        ]);
    }
}
