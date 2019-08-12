<?php
$username = $_POST['username'];
$password = $_POST['password'];
$groupName = htmlspecialchars($_POST['group']);
$userExplode = explode('@', $username);
$samAccountName = $userExplode[0];
$domain = $userExplode[1];
$ldap = "ldaps://" . $domain;

// Connect to AD
$ds = ldap_connect($ldap) or die("Could not connect to LDAP");
ldap_set_option ($ds, LDAP_OPT_REFERRALS, 0) or die('Unable to set LDAP opt referrals');
ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');

// Bind to ldap server
// If bind is successful
if ($bind = ldap_bind($ds, $username, $password)) {
  ?>
  <b>SAM Account Name: </b><?php echo $samAccountName; ?> <br />
  <b>Domain: </b> <?php echo $domain; ?> <br />
  <?php

  // Convert domain name to base DN for lookup
  $ldap_dn = "";
  $domainExplode = explode('.', $domain);
  foreach ($domainExplode as $key => $part) {
    if ($key == 0) {
      $ldap_dn .= "dc=" . $part;
    } else {
      $ldap_dn .= ",dc=" . $part;
    }
  }

  // Display Base OU and LDAP server details
  echo "<b>Base OU: </b>" . $ldap_dn . "<br />";
  echo "<b>LDAP Server: </b>" . $ldap . "<br /><br />";

  // Lookup user by SAMAccountName
  $results = ldap_search($ds,$ldap_dn,"(samaccountName=$samAccountName)",array("memberof"));
  $entries = ldap_get_entries($ds, $results);

  // Return groups
  $groupList = array();
  echo "<b>Groups: </b><br />";
  foreach ($entries[0]['memberof'] as $key => $value) {
    if ($key === "count") {
    } else {
      $exKey = explode(',', $value);
      $group = preg_replace("/CN=/","",$exKey[0]);
      array_push($groupList, $group);
      echo $group . "<br />";
    }
  }

  // Check new array of groups for matching group
  if (in_array($groupName, $groupList)) {
    echo "<br /><br />Found it: " . $groupName;
  }

// If bind fails
} else {
  echo "User lookup fail. Incorrect password?";
}
?>
