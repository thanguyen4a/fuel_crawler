<?php

namespace Model;

class Craw extends \Model {

	public static function insert($datas)
	{
		
   		 $sql = "INSERT INTO craw
	  		( id, title, url,create_date)
	    	VALUES";
    
	    $lastElement = end($datas);
	    
	    foreach ($datas as $data)
	    {
	    	$title = $data['title'];
	    	$url = $data['url'];
	    	$sql .="(,$title,$url,now())";
	    	if($data != $lastElement) {
	    		$sql.= ",";
	    	}
	    }
    		$sql.=';';
    		
    	echo $sql; die;
	}

}

