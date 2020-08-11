<?php

namespace App\Controller;

use App\Repository\FavoriteRepository;
use App\Services\YouShallNotPass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MainController extends AbstractController
{
    private $youShallNotPass;
    private $session;

    public function __construct(YouShallNotPass $youShallNotPass, SessionInterface $session)
    {
        $this->youShallNotPass = $youShallNotPass;
        $this->session = $session;
    }

    /**
     * Method used to render the homepage
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('main/home.html.twig');
    }

    /**
     * Method used to render the profile page
     * @Route("/profile", name="profile")
     */
    public function profile()
    {
        return $this->render('main/profile.html.twig');
    }

    /**
     * Method used in order to have a search bar for our animes and mangas
     * @Route("search", name="search", methods={"POST", "GET"})
     */
    public function search(Request $request, HttpClientInterface $httpClient)
    {
        // Here, we want to retrieve the data that the user typed into the search bar's input
        // We use the ucwords function in order to have a the first character of each string in uppercase
        // And then, the strtolower function to make the typed string in lowercase
        // With this, the user won't be bother to type his research in lowercase or uppercase
        $data = ucwords(strtolower($request->request->get('search')));

        // If the data is null (per example, the search bar's input does not store it anymore), we retrieve the data into the Session
        // This treatment is made in order to have all the user's research informations
        if ($data == null) {
            $data = $this->session->get('lastSearch');
        }
        $this->session->set('lastSearch', $data);
        
        // MANGAS

        // We delay the execution at 2 seconds, because the API limits the request's number in a limited time
        sleep(2);
        // We request the API link that will give us all the informations we need
        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/manga?q='. $data .'&page=1');
        // We retrieve the content from our API request
        $content = $response->getContent();
        // We transform the received data into an array
        $content = $response->toArray();

        // We store the results into a variable
        $mangas = $content['results'];

        // We call our Service in order to do his treatment on the results
        $mangas = $this->youShallNotPass->contentControlBrowse($mangas, YouShallNotPass::MANGA);
        
        //--------

        // ANIMES

        // We delay the execution at 2 seconds, because the API limits the request's number in a limited time
        sleep(2);
        // We request the API link that will give us all the informations we need
        $response = $httpClient->request('GET', 'https://api.jikan.moe/v3/search/anime?q='. $data .'&page=1');
        // We retrieve the content from our API request
        $content = $response->getContent();
        // We transform the received data into an array
        $content = $response->toArray();

        // We store the results into a variable
        $animes = $content['results'];

        // We call our Service in order to do his treatment on the results
        $animes = $this->youShallNotPass->contentControlBrowse($animes, YouShallNotPass::ANIME);

        //--------

        // We make a loop on the manga results in order to retrieve the title and the synopsis
        // We compare them to the user's $data
        // Then, we unset the manga if there is no match
        foreach ($mangas as $index => $manga) {
            $resultTitle = strpos($manga['title'], $data);
            $resultSynopsis = strpos($manga['synopsis'], $data);
            
            if ($resultTitle === false && $resultSynopsis === false) {
                unset($mangas[$index]);
            }
        }

        // We make a loop on the anime results in order to retrieve the title and the synopsis
        // We compare them to the user's $data
        // Then, we unset the anime if there is no match
        foreach ($animes as $index => $anime) {
            $resultTitle = strpos($anime['title'], $data);
            $resultSynopsis = strpos($anime['synopsis'], $data);

            if ($resultTitle === false && $resultSynopsis === false) {
                unset($animes[$index]);
            }
        }

        // We merge our two arrays (mangas and animes) to only have one array
        $arrayMerge = array_merge($mangas, $animes);

        return $this->render('main/search.html.twig', [
            'results' => $arrayMerge,
            'page' => 1,
            'data' => $data
        ]);
    }
}
