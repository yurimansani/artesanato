<?php

   header('Content-Type: application/json');
   # code...
   $letter = isset($_POST['letter']) ? $_POST['letter']: false;
   $id = isset($_POST['id']) ? $_POST['id']: false;

   $response = [];
   $files = [];
   if (!$letter || $id === false)  {
      // http_response_code(412);
      $response['status'] = false;
      $response['message'] = "Esta faltando o parametro 'letter'.";
      echo json_encode($response);
      die();

   }

   // define('URL', '/letter/');
   // define('', 'http://localhost/word/');

   // $response['location'] = URL . $letter . '/';
               
   $dir = getcwd() . '../../../media/letter/' . $letter . '/';

   if (!is_dir($dir)) {
      // http_response_code(412);
      $response['status'] = false;
      $response['message'] = "Esta faltando o parametro 'letter'.";
      echo json_encode($response);
      die();
   }

   //Check to see if this is a real directory
   if ($dh = opendir($dir)) {    //If there is a directory handle -> continue
      while (($file = readdir($dh)) !== false) {   //Do the following steps as long as there is still a file to read
         if ($file != "." && $file != ".." && $file != "BW") {
            array_push($files, $file);    //create an array of files from the directory
         }
      }
      closedir($dh);    //close the directory when finished creating the array
   }

   $rand_key = array_rand($files, 1);

   $response['status'] = true;
   $response['data'] = '/media/letter/' . $letter . '/' .$files[$rand_key];
   $response['id'] = $id;

   echo json_encode($response);
   die();

