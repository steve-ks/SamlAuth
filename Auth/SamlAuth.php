<?php

namespace Kanboard\Plugin\SamlAuth\Auth;

use Kanboard\Core\Base;
use Kanboard\Core\Security\AuthenticationProviderInterface;
use Kanboard\Core\Security\PreAuthenticationProviderInterface;
use Kanboard\Core\Security\SessionCheckProviderInterface;
use Kanboard\Plugin\SamlAuth\User\SamlUserProvider;
use Kanboard\Plugin\SamlAuth\Controller;
use Kanboard\Plugin\SamlAuth\Auth\SamlSettings;

require_once(ROOT_DIR.'/plugins/SamlAuth/Thirdparty/php-saml/_toolkit_loader.php');


class SamlAuth extends Base implements AuthenticationProviderInterface, PreAuthenticationProviderInterface, SessionCheckProviderInterface
{
    const ENV_USERNAME = 'username';
    const ENV_EMAIL = 'email';

    /**
     * User properties
     *
     * @access protected
     * @var SamlUserProvider
     */
    protected $userInfo = null;

    /**
     * Get authentication provider name
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        return 'Saml SSO';
    }

    /**
     * Authenticate the user
     *
     * @access public
     * @return boolean
     */



    public function authenticate(){


      $settings = new SamlSettings($this->configModel);
      try {
          if (isset($_POST['SAMLResponse'])) {
              $samlSettings = new \OneLogin_Saml2_Settings($settings->getSettings(), true);
              $samlResponse = new \OneLogin_Saml2_Response($samlSettings, $_POST['SAMLResponse']);
              if ($samlResponse->isValid()) {

                  /**
                   * DEBUG OUTPUT
                   * Use this for debugging and checking your saml configuration
                   */
                  /*
                  echo 'You are: ' . $samlResponse->getNameId() . '<br>';
                  $attributes = $samlResponse->getAttributes();
                  if (!empty($attributes)) {
                      echo 'You have the following attributes:<br>';
                      echo '<table><thead><th>Name</th><th>Values</th></thead><tbody>';
                      foreach ($attributes as $attributeName => $attributeValues) {
                         echo '<tr><td>' . htmlentities($attributeName) . '</td><td><ul>';
                         foreach ($attributeValues as $attributeValue) {
                             echo '<li>' . htmlentities($attributeValue) . '</li>';
                         }
                         echo '</ul></td></tr>';
                      }
                      echo '</tbody></table>';
                  }
                  echo '<br />Other attributes:<br /><ul>';
                  echo '<li>Config username: ' . $this->configModel->get('samlauth_username_attribute') . '</li>';
                  echo '<li>Config mailaddr: ' . $this->configModel->get('samlauth_email_attribute') . '</li>';
                  echo '</ul><br />';
                  */

                  //Get attributes for SAML from configModel
                  $atrb_email = $this->configModel->get('samlauth_email_attribute');
                  $atrb_username = $this->configModel->get('samlauth_username_attribute');
                  $atrb_firstname = $this->configModel->get('samlauth_firstname_attribute');
                  $atrb_lastname = $this->configModel->get('samlauth_lastname_attribute');
                  $atrb_replacer = $this->configModel->get('samlauth_replace_attribute');
                  $atrb_roles = $this->configModel->get('samlauth_roles_attribute');

                  //Get user information via specified attributes
                  $email =  $samlResponse->getAttributes()["$atrb_email"]['0'];
                  $username = $samlResponse->getAttributes()["$atrb_username"]['0'];
                  $firstname = $samlResponse->getAttributes()["$atrb_firstname"]['0'];
                  $lastname = $samlResponse->getAttributes()["$atrb_lastname"]['0'];
                  $roles = $samlResponse->getAttributes()["$atrb_roles"];

                  //Replace text for a clean username
                  if(!empty($atrb_replacer)) {
                    $username = str_replace($atrb_replacer,"", $username);
                  }
                  //$username = str_replace("companyname\\","", $samlResponse->getNameId());

                  //Check if firstname & lastname is set
                  if(!empty($firstname)) {
                    $name .= $firstname;
                    if(!empty($lastname)) {
                      $name .= ' '.$lastname;
                    }
                  //Otherwise check if lastname
                  } elseif (!empty($lastname)){
                    $name = $lastname;
                  //Otherwise pass empty
                  } else {
                    $name = '';
                  }

                  //Check if username and email are set
                  if (!empty($username) && !empty($email)) {

                      // Check for roles
                      $kbrole = 'app-user';
                      if (!empty($roles)) {
                          foreach ($roles as $role) {
                              $role = strtolower($role);
                              // Check if user is admin or manager
                              if ($role === 'admin' || $role === 'administrator' || $role === 'app-admin') {
                                  // User is admin, early break
                                  $kbrole = 'app-admin';
                                  break;
                              } elseif ($role === 'manager' || $role === 'app-manager') {
                                  // User is manager, continue checking for admin
                                  $kbrole = 'app-manager';
                              }
                          }
                      }

                      // Create user by having email as username
                      $this->userInfo = new SamlUserProvider($username, $email, $name, $kbrole);
                      // Check for a redirectAfterLogin value, make sure that the user is redirected to the correct location
                      if (isset($_POST['RelayState']) && ! filter_var($this->sessionStorage->redirectAfterLogin, FILTER_VALIDATE_URL)
                            && strpos($_POST['RelayState'],'login') === false)
                          $this->response->redirect($_POST['RelayState']);
                      return true;

                  } else {
                    die('Invalid username and email.');
                  }

              } else {
                  die('Invalid SAML Response.');
              }
          } else {
              //If no SAML response then go on with app.
              //die('No SAML Response found in POST.');
              return false;
          }
      } catch (Exception $e) {
          //If Shitty SAML response:
          echo 'Invalid SAML Response: ' . $e->getMessage();
      }

      return false;
    }

    /**
     * Check if the user session is valid
     *
     * @access public
     * @return boolean
     */
    public function isValidSession()
    {
        return !empty($this->userSession->getUsername());
    }

    /**
     * Get user object
     *
     * @access public
     * @return \Kanboard\Plugin\ClientCertificate\User\ClientCertificateUserProvider
     */
    public function getUser()
    {
        return $this->userInfo;
    }
}
