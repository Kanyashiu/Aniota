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
        
        $responseManga = $httpClient->request('GET', 'https://api.jikan.moe/v3/manga/'. $id . '');

        $contentManga = $responseManga->getContent();
        $contentManga = $responseManga->toArray();

        //dd($contentManga);

        $favorite = $favoriteRepository->findByMalId($id);
        if (!$favorite) {
            $favorite = new Favorite();
            $favorite->setTitle($contentManga['title']);
            $favorite->setImage($contentManga['image_url']);
            $favorite->setSynopsis($contentManga['synopsis']);
            $favorite->setMalId($contentManga['mal_id']);
            $favorite->setUser($this->getUser());

            $em->persist($favorite);
        } else {
            foreach ($favorite as $object) {
                //dd($object);
                $em->remove($object);
            }
        }

        $em->flush();
        
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
            //Redirection vers login ?
            // Avec un message flash comme quoi il faut etre connecté pour ajouté un anime ?
            dd('lit les comm jolan :P');
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

            $em->persist($favorite);
        } else {
            foreach ($favorite as $object) {
                //dd($object);
                $em->remove($object);
            }
        }

        $em->flush();
        
        //sleep(2);
        return $this->redirect($_SERVER['HTTP_REFERER']);
        
    }
}
