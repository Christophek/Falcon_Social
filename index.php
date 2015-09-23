<?php

try {
  // Inkludere  LinkedIn classe
  require_once('linkedin_3.1.1.class.php');
  
  // Start på session
  if(!session_start()) {
    throw new LinkedInException('This script requires session support, which appears to be disabled according to session_start().');
  }
  
  // Viser Konstanter
  $API_CONFIG = array(
    'appKey'       => '77540xdawbgehv',
	  'appSecret'    => 'JM95QggN21KC4YGw',
	  'callbackUrl'  => NULL 
  );
  define('CONNECTION_COUNT', 20);
  define('PORT_HTTP', '80');
  define('PORT_HTTP_SSL', '443');
  define('UPDATE_COUNT', 10);

  $type = LINKEDIN::_GET_TYPE;

  // Sæt index
  $_REQUEST[$type] = (isset($_REQUEST[$type])) ? $_REQUEST[$type] : '';

  switch($_REQUEST[$type]) {
    case 'initiate':
      /**
        * håndtere brugere initieret LinkedIn-forbindelse. Opret LinkedIn objekt.
       */
        
      //tjek den korrekte HTTP-protokollen (dvs. er dette script bliver serveret via http eller https)
      if($_SERVER['HTTPS'] == 'on') {
        $protocol = 'https';
      } else {
        $protocol = 'http';
      }
      
        // Indstille tilbagekalds url (adresse)
      $API_CONFIG['callbackUrl'] = $protocol . '://' . $_SERVER['SERVER_NAME'] . ((($_SERVER['SERVER_PORT'] != PORT_HTTP) || ($_SERVER['SERVER_PORT'] != PORT_HTTP_SSL)) ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['PHP_SELF'] . '?' . LINKEDIN::_GET_TYPE . '=initiate&' . LINKEDIN::_GET_RESPONSE . '=1';
      $SimpleLI = new LinkedIn($API_CONFIG);
      
       // Tjek svar fra LinkedIn
      $_GET[LINKEDIN::_GET_RESPONSE] = (isset($_GET[LINKEDIN::_GET_RESPONSE])) ? $_GET[LINKEDIN::_GET_RESPONSE] : '';
      if(!$_GET[LINKEDIN::_GET_RESPONSE]) {
        // LinkedIn har ikke sendt et svar, brugeren indleder forbindelsen
        
        // Send en anmodning om en LinkedIn adgangs token
        $response = $SimpleLI->retrieveTokenRequest();
        if($response['success'] === TRUE) {


        // Opbevar anmodningen token
          $_SESSION['oauth']['linkedin']['request'] = $response['linkedin'];
          
       // Omdirigere brugeren til LinkedIn-godkendelse / autorisation side at iværksætte validering.
          header('Location: ' . LINKEDIN::_URL_AUTH . $response['linkedin']['oauth_token']);
        } else {
       // Dårlig token anmodning (afvist)
          echo "Request token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($SimpleLI, TRUE) . "</pre>";
        }
      } else {
                // LinkedIn har sendt et svar, brugeren har givet tilladelse, tage midlertidig adgang til token, brugerens hemmelige og valideret at anmode brugerens reelle hemmelige nøgle
        $response = $SimpleLI->retrieveTokenAccess($_SESSION['oauth']['linkedin']['request']['oauth_token'], $_SESSION['oauth']['linkedin']['request']['oauth_token_secret'], $_GET['oauth_verifier']);
        if($response['success'] === TRUE) {
                    // Anmodningen gik igennem uden fejl, indsamler brugers adgang 'tokens
          $_SESSION['oauth']['linkedin']['access'] = $response['linkedin'];
          
          // Sætte brugeren er autoriseret til fremtidig hurtig reference
          $_SESSION['oauth']['linkedin']['authorized'] = TRUE;
            
          // Omdirigere brugeren tilbage til Testside/demo/pre-build
          header('Location: ' . $_SERVER['PHP_SELF']);
        } else {
          // Dårlig token adgang
          echo "Access token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($SimpleLI, TRUE) . "</pre>";
        }
      }
      break;

    case 'revoke':
      /**
             * Håndtere tilladelse tilbagekaldelse.
       */
                    
            // Tjekker sessionen
      if(!oauth_session_exists()) {
        throw new LinkedInException('This script requires session support, which doesn\'t appear to be working correctly.');
      }
      
      $SimpleLI = new LinkedIn($API_CONFIG);
      $SimpleLI->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
      $response = $SimpleLI->revoke();
      if($response['success'] === TRUE) {
                // Tilbagekaldelse lykkes, Ryd session
        session_unset();
        $_SESSION = array();
        if(session_destroy()) {
          // Session ødelagt
          header('Location: ' . $_SERVER['PHP_SELF']);
        } else {
          // Sessionen er ikke ødelagt
          echo "Error clearing user's session";
        }
      } else {
         // Tilbagekaldelse mislykkedes
        echo "Error revoking user's token:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre><br /><br />LINKEDIN OBJ:<br /><br /><pre>" . print_r($SimpleLI, TRUE) . "</pre>";
      }
      break;
    default:
      // Test/demo/build/pre
      
    ?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>LinkedIn Assestment - Falcon Social</title>

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
    <link rel="stylesheet" href="bootstrap.min.css">
    <style type="text/css">
      body {
        padding-top: 60px;
      }
    </style>
  </head>

  <body>
    <div class="topbar">
      <div class="fill">
        <div class="container">
          <a class="brand" href="#">LinkedIn Assestment - Falcon Social</a>
        </div>
      </div>
    </div>
    <div class="container">
          <?php

          if(checkSession() === true) {
              // Bruger er allerede tilsluttet
            $SimpleLI = new LinkedIn($API_CONFIG);
            $SimpleLI->setTokenAccess($_SESSION['oauth']['linkedin']['access']);
            ?>
            
            <?php
            $response = $SimpleLI->profile('~:(id,first-name,last-name,headline,industry,summary,location,picture-url,positions,educations,recommendations-received,connections)');
            if($response['success'] === TRUE) {
              $response['linkedin'] = new SimpleXMLElement($response['linkedin']);

              $li_profile = $response['linkedin'];

              ?>
          <div class="well">
            <img src="<?php echo $li_profile->{'picture-url'} ?>" style="float: left; padding-right: 10px;">
            <h1><?php echo $li_profile->{'first-name'} . ' ' . $li_profile->{'last-name'} ?><br />
              <small><?php echo $li_profile->{'headline'} ?></small>


            </h1>
            <p><?php echo $li_profile->{'summary'} ?></p>
            <br><form method="get" action="generate_csv.php">
	
		<input type="hidden" name="limit" value="1"   />
		<input type="submit" name="submit" value="Get CSV File" />
	</form>
	</form>
            <?php 
              if(count($li_profile->{'recommendations-received'}->{'recommendation'}) > 0) {
                foreach ($li_profile->{'recommendations-received'}->{'recommendation'} as $rec) {
                ?>
                <blockquote>
                  <p><?php echo $rec->{'recommendation-text'} ?></p>
                  <small><?php echo $rec->{'recommender'}->{'first-name'} . ' ' . $rec->{'recommender'}->{'last-name'} ?></small>
                </blockquote>
                <?php
                }          
              }

            ?>
          </div>     
          <?php

              if(count($li_profile->{'connections'}->{'person'}) > 0) { 
                $connections = $li_profile->{'connections'}->{'person'};
              ?>
              
              <ul class="media-grid">
              <?php
                $con_count = 0;
                $show_cons = 16; // Antal forbindelser at vise
                foreach ($connections as $person) {
                  if($con_count >= $show_cons) continue;
                  if(!isset($person->{'picture-url'})) continue;
                  $con_count++;
                ?>
                  <li><a href="<?php echo $person->{'site-standard-profile-request'}->{'url'} ?>"><img class="thumbnail" src="<?php echo $person->{'picture-url'} ?>" title="<?php echo $person->{'first-name'} . ' ' . $person->{'last-name'} . ', ' . $person->{'headline'} ?>"></a></li>
                <?php
                }          
              ?>
              </ul>
              <br>
              <?php
              }

              ?>
              <div class="row">
              <?php
              if(count($li_profile->{'positions'}->{'position'}) > 0) { 
              ?>
              
                <div class="span8">
                  <div class="well">
                    <strong>Work Experience:</strong>
              
                    <ul>
                    <?php
                      foreach ($li_profile->{'positions'}->{'position'} as $pos) {
                      ?>
                        <li><?php echo $pos->{'title'} . ', ' . $pos->{'company'}->{'name'} ?></li>
                      <?php
                      }          
                    ?>
                    </ul>
                  </div>
                </div>
              <?php
              }

              if(count($li_profile->{'educations'}->{'education'}) > 0) {
              ?>
                <div class="span8">
                  <div class="well">
                    <strong>Education:</strong>
              
                    <ul>
                    <?php
                      foreach ($li_profile->{'educations'}->{'education'} as $edu) {
                      ?>
                        
                        <li><?php echo $edu->{'school-name'} ?></li>
                      <?php
                      }          
                    ?>
                    </ul>
                  </div>
                </div>
              <?php
              }

              ?>
              </div><!-- erhvervserfaring / uddannelse række -->

              <?php

              //echo "<pre>";
              //print_r($li_profile);

            } else {
              // Profil hentning mislykkedes
              echo "Error retrieving profile information:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response) . "</pre>";
            } 
            ?>
          <!--</div>--> <!-- hero-unit --><?php
          } else {
            // Bruger er ikke tilsluttet
            ?>
          <div class="hero-unit">
            <h1>Show LinkedIn Profile</h1>
            <p>This app connects to the LinkedIn API and shows user's basic profile.</p>
            <p><a class="btn primary large" href="?<?php echo $type ?>=initiate">Connect to LinkedIn &raquo;</a></p>
          </div>
            <?php
          }
          ?>
        </body>
      </html>
      <footer><b>Skills Assestment:</b><br />
      <p style="font-size:11px;"> 1. LinkedIn Login<br /> 2. Extract data from LinkedIn<br /> 3. Get CSV File (without Database, due to development on a different computer)
      </p>
      </footer>

    </div> <!-- /container -->
  </body>
</html>
      <?php
      break;
  }
} catch(LinkedInException $e) {
    // Undtagelse rejst af biblioteket opkald
  echo $e->getMessage();
}

function checkSession() {
    $_SESSION['oauth']['linkedin']['authorized'] = (isset($_SESSION['oauth']['linkedin']['authorized'])) ? $_SESSION['oauth']['linkedin']['authorized'] : FALSE;

    return $_SESSION['oauth']['linkedin']['authorized'];
}

function oauth_session_exists() {
  if((is_array($_SESSION)) && (array_key_exists('oauth', $_SESSION))) {
    return TRUE;
  } else {
    return FALSE;
  }
}







?>
