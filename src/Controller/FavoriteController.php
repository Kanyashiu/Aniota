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
     * @Route("/favorite/manga/{id}", name="favorite_manga" , requirements={"id" : "\d+"})
     */
    public function favoriteManga(FavoriteRepository $favoriteRepository, EntityManagerInterface $em, HttpClientInterface $httpClient, $id)
    {

        $result = $this->youShallNotPass->contentControlDetailsManga($id);
        if ($result) {
            throw $this->createNotFoundException("Error 404 Favorite Manga");
        }

        if (!$this->getUser()) {
            $this->addFlash('error', 'You must be logged to add an anime/manga in favorite');
            return $this->redirectToRoute('app_login');
        }
        
        $responseManga = $httpClient->request('GET', 'https://api.jikan.moe/v3/manga/'. $id . '');
        $contentManga = $responseManga->getContent();
        $contentManga = $responseManga->toArray();


        $favorite = $favoriteRepository->findByMalId($id);
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

        $url = substr($_SERVER['HTTP_REFERER'], -6);
        if ( $url == "search" ) {
            return $this->redirectToRoute('search');
        }

        //sleep(2);
        return $this->redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * @Route("/favorite/anime/{id}", name="favorite_anime" , requirements={"id" : "\d+"})
     */
    public function favoriteAnime(FavoriteRepository $favoriteRepository, EntityManagerInterface $em, HttpClientInterface $httpClient, $id) {
        
        $result = $this->youShallNotPass->contentControlDetailsAnime($id);
        if ($result) {
            throw $this->createNotFoundException("Error 404 Favorite Anime");
        }

        if (!$this->getUser()) {
            $this->addFlash('error', 'You must be logged to add an anime/manga in favorite');
            return $this->redirectToRoute('app_login');
        }

        $responseAnime = $httpClient->request('GET', 'https://api.jikan.moe/v3/anime/'. $id . '');
        $contentAnime = $responseAnime->getContent();
        $contentAnime = $responseAnime->toArray();


        $favorite = $favoriteRepository->findByMalId($id);
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

        $url = substr($_SERVER['HTTP_REFERER'], -6);
        if ( $url == "search" ) {
            return $this->redirectToRoute('search');
        }
        
        //sleep(2);
        return $this->redirect($_SERVER['HTTP_REFERER']);
        
    }
}
