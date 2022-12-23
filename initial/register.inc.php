<?php

session_start();

require '../assets/includes/auth_functions.php';
require '../assets/includes/datacheck.php';
require '../assets/includes/security_functions.php';

check_logged_out();


if (isset($_POST['signupsubmit'])) {

    /*
    * -------------------------------------------------------------------------------
    *   Securing against Header Injection
    * -------------------------------------------------------------------------------
    */

    foreach($_POST as $key => $value){

        $_POST[$key] = _cleaninjections(trim($value));
    }

    /*
    * -------------------------------------------------------------------------------
    *   Verifying CSRF token
    * -------------------------------------------------------------------------------
    */

    if (!verify_csrf_token()){

        $_SESSION['STATUS']['signupstatus'] = 'Request could not be validated';
        header("Location: ../initial");
        exit();
    }



    require '../assets/setup/db.inc.php';
    
    //filter POST data
    function input_filter($data) {
        $data= trim($data);
        $data= stripslashes($data);
        $data= htmlspecialchars($data);
        return $data;
    }
    
    $username = input_filter($_POST['username']);
    $email = input_filter($_POST['email']);
    $hgbs = input_filter($_POST['password']);
    $password = input_filter($_POST['password']);
    $passwordRepeat  = input_filter($_POST['confirmpassword']);

    $full_name = input_filter($_POST['first_name']);
    $last_name = input_filter($_POST['last_name']);
    $level = input_filter($_POST['level']);
    $department = input_filter($_POST['department']);

    if (isset($_POST['gender'])) 
        $gender = input_filter($_POST['gender']);
    else
        $gender = NULL;


    /*
    * -------------------------------------------------------------------------------
    *   Data Validation
    * -------------------------------------------------------------------------------
    */

    if (empty($username) || empty($email) || empty($password) || empty($passwordRepeat)) {

        $_SESSION['ERRORS']['formerror'] = 'required fields cannot be empty, try again';
        header("Location: ../initial");
        exit();
    } else if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {

        $_SESSION['ERRORS']['usernameerror'] = 'invalid username';
        header("Location: ../initial");
        exit();
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $_SESSION['ERRORS']['emailerror'] = 'invalid email';
        header("Location: ../initial");
        exit();
    } else if ($password !== $passwordRepeat) {

        $_SESSION['ERRORS']['passworderror'] = 'passwords donot match';
        header("Location: ../initial");
        exit();
    } else {

        if (!availableUsername($conn, $username)){

            $_SESSION['ERRORS']['usernameerror'] = 'username already taken';
            header("Location: ../initial");
            exit();
        }
        if (!availableEmail($conn, $email)){

            $_SESSION['ERRORS']['emailerror'] = 'email already taken';
            header("Location: ../initial");
            exit();
        }


        /*
        * -------------------------------------------------------------------------------
        *   User Creation
        * -------------------------------------------------------------------------------
        */

        $sql = "insert into users(username, email, password, first_name, last_name, gender,level,department) 
                values ( ?,?,?,?,?,?,?,?)";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {

            $_SESSION['ERRORS']['scripterror'] = 'SQL ERROR';
            header("Location: ../initial");
            exit();
        } 
        else {

            $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt,"ssssssss",$username, $email, $hashedPwd, $full_name, $last_name, $gender,$level,$department);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            /*
            * -------------------------------------------------------------------------------
            *   Sending Verification Email for Account Activation
            * -------------------------------------------------------------------------------
            */
            
            require 'verification.inc.php';
            
            $_SESSION['STATUS']['signupstatus'] = 'Account Created Successfully';
            header("Location: ../initial");
            exit();
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} 
else {

    header("Location: ../initial");
    exit();
}
