<?php

namespace App\Services;

use App\Repository\ExcludeAnimeRepository;
use App\Repository\ExcludeMangaRepository;

class YouShallNotPass
{
    private $count = 0;
    private $excludeManga;
    private $excludeAnime;

    public function __construct(ExcludeMangaRepository $excludeManga, ExcludeAnimeRepository $excludeAnime)
    {
        $this->excludeManga = $excludeManga;
        $this->excludeAnime = $excludeAnime;
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
                return true;
            }
        }
        return false;
    }

    public function contentControlBrowseAnime($dataArray) {
        
        foreach ($dataArray as $index => $data) {
            $animes = $this->excludeAnime->findByMalId($data['mal_id']);
            
            if ($animes != null) {
                unset($dataArray[$index]);
            }
        }
        return $dataArray;
    }

    public function contentControlDetailsAnime($dataToFind) {
        
        $animes = $this->excludeAnime->findByMalId($dataToFind);

        if ($animes != null) {
            return true;
        }
        return false;
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