<?php

/**
 * Created by PhpStorm.
 * User: Vlad
 * Date: 27.01.2016
 * Time: 11:41
 */
class Recomendations
{

	public static function generateVideos()
	{
		foreach (range(1,1000) as $item) {
			$sth = Db::getInstance()->prepare("insert into videos (likes,dislikes, views, categories) values
				(
					RAND()*1000,
					RAND()*1000,
					RAND()*1000,
					concat(FLOOR(RAND()*100),',', FLOOR(RAND()*1000),',',FLOOR(RAND()*1000),',',FLOOR(RAND()*1000))
				)
 				");
			$sth->execute();
		}
	}

	public static function generateRecomendations()
	{
		$ciLowerBoundsQuery = Db::getInstance()->prepare('SELECT
			video_id, categories,
			((likes + 1.9208) / (likes + dislikes) -
				1.96 * SQRT((likes * dislikes) / (likes + dislikes) + 0.9604) /
				(likes + dislikes)) / (1 + 3.8416 / (likes + dislikes))
			AS ci_lower_bound
			FROM videos WHERE likes + dislikes > 0
			ORDER BY ci_lower_bound DESC limit 3000;');
		$ciLowerBoundsQuery->execute();
		$ciLowerBounds = $ciLowerBoundsQuery->fetchAll();
		foreach ($ciLowerBounds as $ciLowerBound) {
			$categories = explode(',', $ciLowerBound['categories']);
			foreach ($categories as $category) {
				$query = Db::getInstance()->prepare('SELECT distinct video_id from video_recomendations where category_id = :category_id and video_id = :video_id');
				$query->bindParam(':category_id', $category);
				$query->bindParam(':video_id', $ciLowerBound['video_id']);
				$query->execute();
				$issetRecomendations = $query->fetchAll();

				if (count($issetRecomendations)){
					$sth = Db::getInstance()->prepare("update video_recomendations set ci_lower_bound=:ci_lower_bound  where category_id = :category_id and video_id = :video_id");
				} else {
					$sth = Db::getInstance()->prepare("insert into video_recomendations (video_id, ci_lower_bound, category_id) values (:video_id, :ci_lower_bound, :category_id)");
				}
				$sth->bindParam(':ci_lower_bound', $ciLowerBound['ci_lower_bound']);
				$sth->bindParam(':video_id', $ciLowerBound['video_id']);
				$sth->bindParam(':category_id', $category);
				$sth->execute();
			}
		}
	}


	public static function getBestVideosByCategory($category)
	{
		$query = Db::getInstance()->prepare("
			select DISTINCT  video_id
			from video_recomendations
			where category_id = :category_id
			ORDER BY  ci_lower_bound DESC
			limit 10
	 	");
		$query->bindParam(':category_id', $category);

		$query->execute();
		$videos = $query->fetchAll();
		$res = [];
		foreach ($videos as $video) {
			$res[] = $video["video_id"];
		}
		return $res;
	}
}