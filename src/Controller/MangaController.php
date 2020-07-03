<?php

namespace App\Controller;

use App\Services\YouShallNotPass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MangaController extends AbstractController
{

    private $youShallNotPass;

    public function __construct(YouShallNotPass $youShallNotPass)
    {
        $this->youShallNotPass = $youShallNotPass;
    }
    /**
     * @Route("/manga", name="manga", methods={"GET"})
     */
    public function browse(HttpClientInterface $httpClient, Request $request)
    {
        $page = $request->query->get('page');
        
        $mangaGenre = json_decode(file_get_contents("assets/json/manga-genre.json"), true);

        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }

        if (isset($_GET['genre']) && !empty($_GET['genre'])) {
            $genre = $_GET['genre'];

            $result = $this->youShallNotPass->typeControlBrowseManga($mangaGenre, $genre);
            if ($result) {
                throw $this->createNotFoundException("Error 404 Browse Manga ( genre '".$genre."' )");
            }
    
        }
        else {
            $genre = '0';
        }

        if (isset($_GET['sort']) && !empty($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        else {
            $sort = 'ASC';
        }

        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?order_by=title&type=manga&page=' . $page . '&genre=' . $genre . '&sort=' . $sort . '');

        $content = $response->getContent();
        $content = $response->toArray();

        $results = $content['results'];

        
        
        $results = $this->youShallNotPass->contentControlBrowseManga($results);

        return $this->render('manga/index.html.twig', [
            'mangas' => $results,
            'page' => $page,
            'jsonGenre' => $mangaGenre
        ]);
    }

    /**
     * @Route("/manga/{id}", name="manga_details", methods={"GET"})
     */
    public function details(HttpClientInterface $httpClient, $id)
    {
        $result = $this->youShallNotPass->contentControlDetailsManga($id);
        if ($result) {
            throw $this->createNotFoundException("Error 404 Details Manga");
        }

        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/manga/' . $id . '');

        $mangas = $response->getContent();
        $mangas = $response->toArray();

        return $this->render('manga/details.html.twig', [
            'mangas' => $mangas,
        ]);
    }
}
