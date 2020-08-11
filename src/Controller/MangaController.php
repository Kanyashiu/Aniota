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
     * Method used to have the list of all animes
     * @Route("/manga", name="manga", methods={"GET"})
     */
    public function browse(HttpClientInterface $httpClient, Request $request)
    {
        // We request the 'page' GET parameter and store it
        $page = $request->query->get('page');
        
        // We retrieve the json file for manga genre and convert it as array
        $mangaGenre = json_decode(file_get_contents("assets/json/manga-genre.json"), true);

        // If we have a 'page' GET parameter, we set it into a variable
        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }

            // If we have a 'sort' GET parameter and the 'genre' GET parameter is not set, we set it into a variable
            // Then, we request the API link that will give us all the informations we need
            // We do the same with the 'genre' parameter GET, we check if it's set
            // We set the 'sort' GET parameter as 'Ascending' to get our mangas in an ascending order
            // Then we use our YouShallNotPass Service in order to have a filter to the manga list genre (hentai per example)
            // Finally, we request the API link that will give us all the informations we need
            // We send a 404 error if we get an inappropriate manga
            if (isset($_GET['sort']) && !empty($_GET['sort']) && !isset($_GET['genre'])) {

                $sort = $_GET['sort'];

                $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?order_by=title&sort=' . $sort . '&page=' . $page . '');
            }
            if (isset($_GET['genre']) && !empty($_GET['genre'])) {

                $genre = $_GET['genre'];

                $sort = $_GET['sort'] ?? 'asc';
        
                    $result = $this->youShallNotPass->typeControlBrowse($mangaGenre, $genre);
                    if ($result) {
                        throw $this->createNotFoundException("Error 404 Browse Manga ( genre '".$genre."' )");
                    }
                
                $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?genre=' . $genre . '&sort='. $sort . '&page=' . $page . '');
            }
            else {

                $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?order_by=title&type=manga&page=' . $page . '');
            }

        // We retrieve the content from our API request
        $mangas = $response->getContent();
        // We transform the received data into an array
        $mangas = $response->toArray();
        
        // We store the results into a variable
        $results = $mangas['results'];

        // We call our Service in order to do his treatment on the results
        $results = $this->youShallNotPass->contentControlBrowse($results, YouShallNotPass::MANGA);

        return $this->render('manga/index.html.twig', [
            'mangas' => $results,
            'page' => $page,
            'sort' => $sort ?? null,
            'genre' => $genre ?? null,
            'jsonGenre' => $mangaGenre
        ]);
    }

    /**
     * Method used in order to have the details of one manga using his id
     * @Route("/manga/{id}", name="manga_details", methods={"GET"})
     */
    public function details(HttpClientInterface $httpClient, $id)
    {
        // Same treatment as for the browse method
        $result = $this->youShallNotPass->contentControlDetails($id, YouShallNotPass::MANGA);
        if ($result) {
            throw $this->createNotFoundException("Error 404 Details Manga");
        }

        // We request the API link that will give us all the informations we need
        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/manga/' . $id . '');

        // We retrieve the content from our API request
        $mangas = $response->getContent();
        // We transform the received data into an array
        $mangas = $response->toArray();

        return $this->render('manga/details.html.twig', [
            'mangas' => $mangas,
        ]);
    }
}
