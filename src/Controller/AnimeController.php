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
     * Method used to have the list of all animes
     * @Route("/anime", name="anime", methods={"GET"})
     */
    public function browse()
    {

        // We call the HttpClientInterface
        $client = HttpClient::create();
        // We retrieve the json file for anime genre and convert it as array
        $animeGenre = json_decode(file_get_contents("assets/json/anime-genre.json"), true);
        // We retrieve the json file for anime rating and convert it as array
        $animeRating = json_decode(file_get_contents("assets/json/anime-rating.json"), true);
        
        // If we have a 'page' parameter GET, we set it into a variable
        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $page = $_GET['page'];
        }
        else {
            $page = 1;
        }
        
        // If we have a 'rated' parameter GET, we set it into a variable
        // And we use our YouShallNotPass Service in order to have a filter to the anime list rating (+18 per example)
        // Finally, we request the API link that will give us all the informations we need
        // We do the same with the 'genre' parameter GET, we check if it's set
        // Then we use our YouShallNotPass Service in order to have a filter to the anime list genre (hentai per example)
        // Finally, we request the API link that will give us all the informations we need
        // We send a 404 error if we get an inappropriate anime
        if (isset( $_GET['rated']) && !empty( $_GET['rated'])) {
            $rating = $_GET['rated'];
            
            $result = $this->youShallNotPass->typeControlBrowseManga($animeRating, $rating);
            if ($result) {
                throw $this->createNotFoundException("Error 404 Browse Anime ( rated '".$rating."' )");
            }

            $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?rated=' . $rating . '&page=' . $page . '');
        }
        else if (isset( $_GET['genre']) && !empty( $_GET['genre'])) {
            
            $genre = $_GET['genre'];

            $result = $this->youShallNotPass->typeControlBrowseManga($animeGenre, $genre);
            if ($result) {
                throw $this->createNotFoundException("Error 404 Browse Anime ( genre '".$genre."' )");
            }
            
            $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?genre=' . $genre . '&page=' . $page . '');
        }
        else {
            $response = $client->request('GET', 'https://api.jikan.moe/v3/search/anime?order_by=title&page=' . $page . '');
        }

        //! To handle the 429 error later
        //! For the untimely refresh
        // $statusCode = $response->getStatusCode();
        
        // We convert the response into an array
        $animes = $response->toArray();

        // We call our Service in order to do his treatment on the results
        $animes = $this->youShallNotPass->contentControlBrowseAnime($animes['results']);

        return $this->render('anime/index.html.twig', [
            'animes' => $animes,
            'page' => $page,
            'rated' => $rating ?? null,
            'genre' => $genre ?? null,
            'animeGenre' => $animeGenre,
            'animeRating' => $animeRating,
        ]);
    }

    /**
     * Method used in order to have the details of one anime using his id
     * @Route("/anime/{id}", name="anime_details", requirements={"id": "\d+"}, methods={"GET"})
     */
    public function details($id)
    {
        // Same treatment as for the browse method
        $result = $this->youShallNotPass->contentControlDetailsAnime($id);
        if ($result) {
            throw $this->createNotFoundException("Error 404 Details Anime");
        }

        // We call the HttpClientInterface
        $client = HttpClient::create();
        // We request the API link that will give us all the informations we need
        $response = $client->request('GET', 'https://api.jikan.moe/v3/anime/' . $id . '');

        // We convert the response into an array
        $anime = $response->toArray();

        return $this->render('anime/details.html.twig', [
            'anime' => $anime
        ]);
    }

}
