<?php
    //Author: Sahil Shrestha
    //Heavily based on the deploy.php used in the private server
    if ((isset($_POST['username'])) && (isset($_POST['password'])) ){
        $user_authentication_file_path = "../live_deploy_credentials/user.ini";
        $git_config_file_path = "../live_deploy_credentials/db.ini"; //where the PAT from github is stored
        $branch = 'main'; // should always be 'main' or 'master'

        $user_config = parse_ini_file($user_authentication_file_path);
        $user = $user_config['user'];
        $password = $user_config['password'];

        //verify the credentials first
        if (!( ($_POST['username']==$user) && ($_POST['password']==$password))){
            die ("Authentication Failed");
        }
        
        //check if the credential file exists or not
        if (file_exists($git_config_file_path)){
            $git_config = parse_ini_file($git_config_file_path);
            
            $token = $git_config['token'];
            
            //VERIFY THE PERSONAL ACCESS TOKEN 
            $authorizationHeader = 'Authorization: token ' . $token;// Set the authorization header with the access token

            // Execute the command with cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorizationHeader, 'User-Agent: PHP'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            // Check if the response contains an error
            if (strpos($response, 'Bad credentials') !== false) {
                die('Error: Invalid personal access token.');
            }

            //commands to pull from the git
            $commands = array(
                'echo $PWD',
                'whoami',
                'git reset --hard HEAD',
                'git checkout '.$branch,
            );
            // Run the commands for output
            $output = '';
            foreach($commands AS $command){
                // Run it
                $tmp = shell_exec($command." 2>&1");
                // Output
                $output .= "<span style=\"color: #6BE234;\">\$</span> <span style=\"color: #729FCF;\">{$command}\n</span>";
                $output .= htmlentities(trim($tmp)) . "\n";
            }
            //the main command that uses personal access token from git and pull from the remote
            $pull_command =  'echo "' . $token . '" | git pull';
            $tmp = shell_exec($pull_command." 2>&1");
            // Output
            $output .= "<span style=\"color: #6BE234;\">\$</span> <span style=\"color: #729FCF;\">git pull\n</span>";
            $output .= htmlentities(trim($tmp)) . "\n";  
        }else{
            echo "<h1 style='color:red; text-align:center'>DEPLOYMENT UNSUCCESSFUL. CHECK THE LOCATION OF THE CREDENTIAL FILE</h1>";
            die();
        }
    }else{
        die("Unauthorized Access");
    }
    
?>

<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>GIT DEPLOYMENT SCRIPT</title>
</head>
<body style="background-color: #000000; color: #FFFFFF; font-weight: bold; padding: 0 10px;">
<pre>
 ____________________________
|                            |
| Git Deployment Script v0.1 |
|      github.com/riodw 2017 |			 
|____________________________|

<?php
echo "Executed at: " . date("h:i:sa");
?>

<?php echo $output; ?>
</pre>
</body>
</html>