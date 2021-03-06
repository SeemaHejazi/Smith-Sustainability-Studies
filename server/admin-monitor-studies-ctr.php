<?php
// load file to authenticate user and then determine if the authenticated user has permission to access this page
require_once 'utils/authenticateUser.php';
verifyUserPrivilage('admin');

// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];                                 // GET,POST,PUT,DELETE
// $userID = '1';
$userID = $_SESSION['userID'];

// create SQL based on HTTP method
switch ($method) {
  case 'GET':
    // need to check if this exists: ISSET?
    if(!isset($_GET["q"])) {
      htpp_response_code(500);
    }

    $queryType = $_GET["q"]; // don't need userID, as will use $_SESSION
    switch ($queryType) {
      case 'get_admin_studies':
        $error = false;
        $studies = getAdminStudies($userID);
      //error_log(print_r($studies, true), 0);
        if ($studies == null) {
            $error = true;
            $errorMsg = 'No studies found';
            //error_log("some error", 0);
        }
        else
            $errorMsg = 'Admin studies found';
        //error_log("through", 0);
        echo json_encode(array(
                  "error" => $error,
                  "errorMsg" => $errorMsg, 
                  "data" => $studies));
        break;
      case 'get_study_data':
          $error = false;
          $studies = null;
          $conditionGroupPhase = null;
          $posts = null;

          $studyIDIn = cleanInputGet('studyID');
         // error_log("get study data");
          if (empty($studyIDIn)) {
              $error = true;
              $errorMsg = 'Expecting studyID';
             // error_log("missing study id");
          }
          
          $studies = getAdminStudies($userID);
          if ($studies == null) {
              $error = true;
              $errorMsg = 'No studies found';
             // error_log("no studies found");
          }
          else {
              $errorMsg = 'Admin studies found.';

              $conditionGroupPhase = getAdminConditionGroupPhase($userID);
              if ($conditionGroupPhase == null) {
                  $error = true;
                  $errorMsg = 'Database error accessing conditionGroupTable';
                 // error_log("no condition group phase");
              }
              else {
                  $posts = getStudyPosts($studyIDIn);
                  if ($posts == null) {
                      $error = true;
                      $errorMsg = 'Database error accessing postTable';
                     // error_log("no posts");
                  }
              }
          }
         // error_log(print_r($posts, true), 0);
          echo json_encode(array(
                    "error" => $error,
                    "errorMsg" => $errorMsg, 
                    "studies" => $studies,
                    "conditionGroupPhase" => $conditionGroupPhase,
                    "posts" => $posts));
          break;
      case 'get_single_study_data':
        $error = false;
        $studyID = $_GET["studyID"];
        $study = getStudy($studyID);
        $conditionGroupPhase = getAdminConditionGroupPhase($userID);
        
        if ($study == null || $conditionGroupPhase == null) {
            $error = true;
            $errorMsg = 'No study or condition group phase found';
           // error_log("some error", 0);
        }
        else
            $errorMsg = 'Study and condition group phase found';
        echo json_encode(array(
                  "error" => $error,
                  "errorMsg" => $errorMsg, 
                  "study" => $study,
                  "conditionGroupPhase" => $conditionGroupPhase));
       // error_log("returned from study: ",0);
       // error_log(print_r($study, true),0);
        break;
      case 'get_results':
        error_log("GET daily entries - admin monitor studies", 0);
        $error = false;
        $studyID = cleanInputGet("studyID");
        $dailyEntries = getStudyDailyEntries($studyID);
        if ($dailyEntries == null) {
            $error = true;
            $errorMsg = 'No Daily Entries found';
            error_log("some error", 0);
        }
        else
            $errorMsg = 'Daily Entries found';
        //error_log("before echo", 0);
        echo json_encode(array(
                  "error" => $error,
                  "errorMsg" => $errorMsg, 
                  "data" => $dailyEntries));
        //error_log("after echoed",0);
    }

    break;
  case 'PUT':                              // not required in this controller
  case 'POST':
   // error_log("Posting new post");
    $error = false;
    //$postRecord = null;

//    $dateTimeStamp = cleanInputPost("dateTime1");
    date_default_timezone_set('America/Toronto');
    $dateTimeStamp = date('Y-m-d H:i:s'); // when the study is made active
//error_log("testing ".$FILES[])
    $postText = cleanInputPost("text1");
    //$imageName = $_FILES["image1"]["name"];//cleanInputPost("image1");
    $image = cleanInputPost("image1"); //addslashes(file_get_contents($_FILES['image1']['tmp_name']));
    $conditionGroupNum = cleanInputPost("conditionGroupNum1");
    $phaseNum = cleanInputPost("phaseNum1");
    $studyID = cleanInputPost("studyID1");

/*    $postText = $_FILES['text1'];
    $image = $_FILES['image1'];
    $conditionGroupNum = $_FILES['conditionGroupNum1'];
    $phaseNum = $_FILES['phaseNum1'];
    $studyID = $_FILES['studyID1'];*/
    error_log("userID: ".$userID." dateTime: ".$dateTimeStamp." postText: ".$postText." conditionGroupNum: ".$conditionGroupNum." phase: ".$phaseNum." study: ".$studyID, 0);

    //error_log(print_r($image, true), 0);

    if (empty($postText)) {
        $error = true;
        $errorMsg = 'Text is required.';
       // error_log("text required");
    }
    if (empty($conditionGroupNum)) {
        $error = true;
        $errorMsg = 'condition group required.';
    }
    if (empty($phaseNum)) {
        $error = true;
        $errorMsg = 'phase required.';
    }

    // image code
    
    /*$uploadImage = $_FILES['image']['tmp_name'];
error_log("image: ".$uploadImage);
    if($uploadImage =="") {
error_log("image is '' ");
      $uploadImage = " ";
    }*/
    

    if(!$error) {
     // error_log("no error");
        $postRecord = createPost($userID, $dateTimeStamp, $postText, $image, $conditionGroupNum, $phaseNum, $studyID);  
//error_log("image is: ".$postRecord['image']);
        
        if ($postRecord == null) {
            $error = true;
            $errorMsg = 'Database error: Could not create post';
        }  else {
          $errorMsg = 'Post Created';
        }
    }

    echo json_encode(array(
              "error" => $error,
              "errorMsg" => $errorMsg,
              "data" => $postRecord));

    break;
                            
  case 'DELETE':
   // error_log("got into admin-monitor-users - DELETE");
        $error = false;
        // get parameters
        parse_str($_SERVER['QUERY_STRING'], $query_params);
        if (!isset($query_params['postID'])) {
            $error = true;
            $errorMsg = 'No post specified';
        }
        else if (!ctype_digit($query_params['postID'])){       // must be all digits
            $error = true;
            $errorMsg = 'Illegal post specified';
        }
        else {
            // check if there was an database error or nothing returned
            $postID = $query_params['postID'];
            
            if (!deletePost($postID)) {
                $error = true;
                $errorMsg = 'No post  found';
            }
            else
                $errorMsg = 'Post deleted';        
        }
        echo json_encode(array(
                  "error" => $error,
                  "errorMsg" => $errorMsg));
        break;
        
    default:
        http_response_code(404);
        echo "Error: Unrecognised request.";
        echo json_encode(array(
                  "error" => true,
                  "errorMsg" => "Error: Unrecognised request."));
        break;
}


function getStudyPosts($studyID) {
    $conn = dbConnect();


// TODO: Do join with matching study, phase and cg num params
    $sql =  "SELECT * ".
            "FROM postTable ".
            "WHERE studyID='".$studyID."'".
            " ORDER BY postTable.postID DESC ;";
 
    $result = mysqli_query($conn, $sql);
//error_log($sql);
    // check if any records found. If records found, gather them into an array and return the array
    if ($result == false)
        $rows = null;
    else {
        $rows = array();
        while($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
        
    mysqli_close($conn);    
    return $rows;

}   
