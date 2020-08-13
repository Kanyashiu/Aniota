<?php

namespace App\Services;

use App\Repository\ExcludeAnimeRepository;
use App\Repository\ExcludeMangaRepository;

class YouShallNotPass
{
    private $count = 0;
    private $excludeManga;
    private $excludeAnime;

    const MANGA = 'Manga';
    const ANIME = 'Anime';

    public function __construct(ExcludeMangaRepository $excludeManga, ExcludeAnimeRepository $excludeAnime)
    {
        $this->excludeManga = $excludeManga;
        $this->excludeAnime = $excludeAnime;
    }

    /**
     * This method is used to check with an array sent as argument ($arrayUsedToVerify) and a data to be found ($dataToFind), 
     * that the data to be found is present in the array sent
     * 
     * @param array $arrayUsedToVerify
     * @param string $dataToFind
     * @return true|false
     */
    public function typeControlBrowse($arrayUsedToVerify, $dataToFind)
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
     * This method is used to filter an array containing anime/manga ( $dataArray ), 
     * in order to exclude from this array the anime not authorized by our application
     * with our constants we can call the right entity
     * 
     * @param array $dataArray
     * @param const $entity
     * @return array
     */
    public function contentControlBrowse($dataArray, $entity = Self::MANGA) 
    {
        if ($entity == Self::MANGA ) {
            $entity = $this->excludeManga;
        }
        else if ($entity == Self::ANIME ) {
            $entity = $this->excludeAnime;
        }

        foreach ($dataArray as $index => $data) {
            $result = $entity->findByMalId($data['mal_id']);
            
            if ($result != null) {
                unset($dataArray[$index]);
            }
        }
        return $dataArray;
    }


    /**
     * This method is used to check that an anime id sent as argument ($dataToFind), 
     * is not present in our database in order to exclude unauthorized anime/manga from our application
     * with our constants we can call the right entity
     *
     * @param string $dataToFind
     * @param const $entity
     * @return true|false
     */
    public function contentControlDetails($dataToFind, $entity = self::MANGA) {

        if ($entity == Self::MANGA ) {
            $entity = $this->excludeManga;
        }
        else if ($entity == Self::ANIME ) {
            $entity = $this->excludeAnime;
        }
        
        $result = $entity->findByMalId($dataToFind);

        if ($result != null) {
            return true;
        }
        return false;
    }
}