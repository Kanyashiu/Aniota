<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use App\Services\YouShallNotPass;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FavoriteController extends AbstractController
{
    private $youShallNotPass;

    public function __construct(YouShallNotPass $youShallNotPass)
    {
        $this->youShallNotPass = $youShallNotPass;
    }
    
    /**
     * This method will allow to add a manga in your favorite
     * @Route("/favorite/manga/{id}", name="favorite_manga" , requirements={"id" : "\d+"})
     */
    public function favoriteManga(FavoriteRepository $favoriteRepository, EntityManagerInterface $em, HttpClientInterface $httpClient, $id)
    {
        // We use our YouShallNotPass service in order to filter an inappropriate manga
        $result = $this->youShallNotPass->contentControlDetailsManga($id);
        if ($result) {
            throw $this->createNotFoundException("Error 404 Favorite Manga");
        }

        // If the user isn't logged, he can't add an anime/manga in favorite
        // So, we redirect him to the login page
        if (!$this->getUser()) {
            $this->addFlash('error', 'You must be logged to add an anime/manga in favorite');
            return $this->redirectToRoute('app_login');
        }
        
        // We request the API link that will give us all the informations we need
        $responseManga = $httpClient->request('GET', 'https://api.jikan.moe/v3/manga/'. $id . '');
        // We retrieve the content from our API request
        $contentManga = $responseManga->getContent();
        // We transform the received data into an array
        $contentManga = $responseManga->toArray();

        // We call the Favorite Repository findByMalId method
        // This method helps us to find a manga with his mal_id
        $favorite = $favoriteRepository->findByMalId($id);
        // If the manga isn't in favorite yet, we create a new Favorite Object
        // Then, we set all the manga content into the appropriate setters and we persist in database and it will be in user's favorite
        // If the manga is already in favorite, we remove it from the database and user's favorite
        // We flush
        if (!$favorite) {
            $favorite = new Favorite();
            $favorite->setTitle($contentManga['title']);
            $favorite->setImage($contentManga['image_url']);
            $favorite->setSynopsis($contentManga['synopsis']);
            $favorite->setMalId($contentManga['mal_id']);
            $favorite->setUser($this->getUser());
            $favorite->setType('Manga');

            $em->persist($favorite);
        } else {
            foreach ($favorite as $object) {
                $em->remove($object);
            }
        }

        $em->flush();

        // Here, we want a redirection on the search url, after a user has made a manga research and put one of them in favorite
        $url = substr($_SERVER['HTTP_REFERER'], -6);
        if ( $url == "search" ) {
            return $this->redirectToRoute('search');
        }

        // We redirect the user on the previous url thanks to the HTTP_REFERER
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * This method will allow to add a anime in your favorite
     * @Route("/favorite/anime/{id}", name="favorite_anime" , requirements={"id" : "\d+"})
     */
    public function favoriteAnime(FavoriteRepository $favoriteRepository, EntityManagerInterface $em, HttpClientInterface $httpClient, $id) {
        
        // We use our YouShallNotPass service in order to filter an inappropriate anime
        $result = $this->youShallNotPass->contentControlDetailsAnime($id);
        if ($result) {
            throw $this->createNotFoundException("Error 404 Favorite Anime");
        }

        // If the user isn't logged, he can't add an anime/manga in favorite
        // So, we redirect him to the login page
        if (!$this->getUser()) {
            $this->addFlash('error', 'You must be logged to add an anime/manga in favorite');
            return $this->redirectToRoute('app_login');
        }

        // We request the API link that will give us all the informations we need
        $responseAnime = $httpClient->request('GET', 'https://api.jikan.moe/v3/anime/'. $id . '');
        // We retrieve the content from our API request
        $contentAnime = $responseAnime->getContent();
        // We transform the received data into an array
        $contentAnime = $responseAnime->toArray();

        // We call the Favorite Repository findByMalId method
        // This method helps us to find an anime with his mal_id
        $favorite = $favoriteRepository->findByMalId($id);
        // If the anime isn't in favorite yet, we create a new Favorite Object
        // Then, we set all the anime content into the appropriate setters and we persist in database and it will be in user's favorite
        // If the anime is already in favorite, we remove it from the database and user's favorite
        // We flush
        if (!$favorite) {
            $favorite = new Favorite();
            $favorite->setTitle($contentAnime['title']);
            $favorite->setImage($contentAnime['image_url']);
            $favorite->setSynopsis($contentAnime['synopsis']);
            $favorite->setMalId($contentAnime['mal_id']);
            $favorite->setUser($this->getUser());
            $favorite->setType('Anime');

            $em->persist($favorite);
        } else {
            foreach ($favorite as $object) {
                $em->remove($object);
            }
        }

        $em->flush();

        // Here, we want a redirection on the search url, after a user has made an anime research and put one of them in favorite
        $url = substr($_SERVER['HTTP_REFERER'], -6);
        if ( $url == "search" ) {
            return $this->redirectToRoute('search');
        }
        
        // We redirect the user on the previous url thanks to the HTTP_REFERER
        return $this->redirect($_SERVER['HTTP_REFERER']);
        
    }
}
