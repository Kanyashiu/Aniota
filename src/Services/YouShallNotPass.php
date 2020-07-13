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

    /**
     * This method is used to check with an array sent as argument ($arrayUsedToVerify) and a data to be found ($dataToFind), 
     * that the data to be found is present in the array sent
     * 
     * @param array $arrayUsedToVerify
     * @param string $dataToFind
     * @return true|false
     */
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

    /**
     * This method is used to filter an array containing anime ( $dataArray ), 
     * in order to exclude from this array the anime not authorized by our application
     * 
     * @param array $dataArray
     * @return array
     */
    public function contentControlBrowseAnime($dataArray) 
    {
        foreach ($dataArray as $index => $data) {
            $animes = $this->excludeAnime->findByMalId($data['mal_id']);
            
            if ($animes != null) {
                unset($dataArray[$index]);
            }
        }
        return $dataArray;
    }

    /**
     * This method is used to check that an anime id sent as argument ($dataToFind), 
     * is not present in our database in order to exclude unauthorized anime from our application
     *
     * @param string $dataToFind
     * @return true|false
     */
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

    /**
     * This method is used to check with an array sent as argument ($arrayUsedToVerify) and a data to be found ($dataToFind), 
     * that the data to be found is present in the array sent
     * 
     * @param array $arrayUsedToVerify
     * @param string $dataToFind
     * @return true|false
     */
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

    /**
     * This method is used to filter an array containing manga ( $dataArray ), 
     * in order to exclude from this array the manga not authorized by our application
     * 
     * @param array $dataArray
     * @return array
     */
    public function contentControlBrowseManga($dataArray) 
    {
        foreach ($dataArray as $index => $data) {
            $mangas = $this->excludeManga->findByMalId($data['mal_id']);
            
            if ($mangas != null) {
                unset($dataArray[$index]);
            }
        }
        return $dataArray;
    }

    /**
     * This method is used to check that an manga id sent as argument ($dataToFind), 
     * is not present in our database in order to exclude unauthorized manga from our application
     *
     * @param string $dataToFind
     * @return true|false
     */
    public function contentControlDetailsManga($dataToFind) {
        
        $manga = $this->excludeManga->findByMalId($dataToFind);

        if ($manga != null) {
            return true;
        }
        return false;
    }
}