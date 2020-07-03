<?php

namespace App\Services;

use App\Repository\ExcludeMangaRepository;

class YouShallNotPass
{
    private $count = 0;
    private $excludeManga;

    public function __construct(ExcludeMangaRepository $excludeManga)
    {
        $this->excludeManga = $excludeManga;
    }

    //===========================
    // Anime method
    //===========================
    public function typeControlBrowseAnime($arrayUsedToVerify, $dataToFind)
    {
        foreach ( $arrayUsedToVerify as $data ) {
            $result = array_search($dataToFind, $data);
            
            $this->count++;

            if ( $result != false ) {
                break;
            }

            if ( count($arrayUsedToVerify) == $this->count )
            {
                throw new \Exception('Error 404 Browse Anime', 404);
            }
        }
    }

    public function contentControlBrowseAnime($dataArray) {

        foreach ($dataArray['results'] as $index => $data )
        {
            if($data['rated'] == "R+" || $data['rated'] == "Rx" )
            {   
                unset($dataArray['results'][$index]);   
            }
        }

        return $dataArray;
    }

    public function contentControlDetailsAnime($dataArray) {

            if($dataArray['rating'] == "R+ - Mild Nudity" || $dataArray['rating'] == "Rx - Hentai" )
            {   
                // Le chemin du fichier
                $file = '../public/assets/json/anime-YSNP.json';

                // Récupère le contenu du fichier ( chaine de caractères avec les saut de ligne)
                $current = file_get_contents($file);
                
                // Le true permet de passe d'un objet STD à un tableau associatif ( tableau associatif)
                $currentDecode = json_decode($current, true);

                // Récupère les nouvelles données à écrire dans le fichier
                $id = $dataArray['mal_id'];
                $rating = $dataArray['rating'];

                $content = [
                    'id' => $id,
                    'rating' => $rating,
                ];

                // Je push dans le tableau
                $currentDecode[] = $content;
                
                // Je le transforme en objet
                $currentDecode = (object) $currentDecode;

                // Je l'encode ( chaine de caractère sans les saut de ligne )
                $currentEncode = json_encode($currentDecode);

                //! Evolution possible parse le fichier pour qu'il afficher une tabulation et des saut de ligne
                //! Pour un ficher plus humanReadible
                //$finalCurrent = str_replace("{", "{\n", $currentEncode);
                //! ==============

                // Écrit le résultat dans le fichier
                file_put_contents($file, $currentEncode);

                throw new \Exception('Error 404 Détails Anime 1', 404);
            }

            return $dataArray;
    }

    public function contentControlExistingDataAnime($dataArray, $dataToFind) {

        if (empty($dataArray))
        {
            return;
        }
        
        foreach ($dataArray as $data)
        {
            $result = array_search($dataToFind, $data);
            
            if ( $result != false)
            {
                throw new \Exception('Error 404 Détails Anime 2', 404);
            }
        }
    }


    //===========================
    // Manga method
    //===========================
    public function typeControlBrowseManga($arrayUsedToVerify, $dataToFind)
    {
        foreach ( $arrayUsedToVerify as $data ) {
            $result = array_search($dataToFind, $data);
            
            $this->count++;

            if ( $result != false ) {
                break;
            }

            if ( count($arrayUsedToVerify) == $this->count )
            {
                return true;
            }
        }
        return false;
    }

    public function contentControlBrowseManga($dataArray) {
        
        foreach ($dataArray as $index => $data) {
            $mangas = $this->excludeManga->findByMalId($data['mal_id']);
            
            if ($mangas != null) {
                unset($dataArray[$index]);
            }
        }
        return $dataArray;
    }

    public function contentControlDetailsManga($dataToFind) {
        
        $manga = $this->excludeManga->findByMalId($dataToFind);

        if ($manga != null) {
            return true;
        }
        return false;
    }
}