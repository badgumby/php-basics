<?php
$username = $_POST['username'];
$password = $_POST['password'];
$groupName = htmlspecialchars($_POST['group']);
$userExplode = explode('@', $username);
$samAccountName = $userExplode[0];
$domain = $userExplode[1];
$ldap = "ldaps://" . $domain;

?>
<html>
<head>
  <title>Auth</title>
  <link rel = "stylesheet" type = "text/css" href = "style/style.css">
</head>
<body>

<?php

// Connect to AD
$ds = ldap_connect($ldap) or die("Could not connect to LDAP");
ldap_set_option ($ds, LDAP_OPT_REFERRALS, 0) or die('Unable to set LDAP opt referrals');
ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');

// Bind to ldap server
// If bind is successful
if ($bind = ldap_bind($ds, $username, $password)) {
  ?>
  <div>
    <table>
      <tr>
        <td>
          <b>SAM Account Name: </b>
        </td>
        <td>
          <?php echo $samAccountName; ?>
        </td>
      </tr>
      <tr>
        <td>
          <b>Domain: </b>
        </td>
        <td>
          <?php echo $domain; ?>
        </td>
      </tr>

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
  ?>
  <tr>
    <td>
      <b>Base OU: </b>
    </td>
    <td>
      <?php echo $ldap_dn; ?>
    </td>
  </tr>
  <tr>
    <td>
      <b>LDAP Server: </b>
    </td>
    <td>
      <?php echo $ldap; ?>
    </td>
  </tr>
</table>
</div>

  <?php
  // Lookup user by SAMAccountName
  $results = ldap_search($ds,$ldap_dn,"(samaccountName=$samAccountName)",array("memberof"));
  $entries = ldap_get_entries($ds, $results);

  // Count number of groups found
  $count = count($entries[0]['memberof']);
  // Subtract the 'count' key
  $count = $count - 1;

  // Return groups
  $groupList = array();
  ?>
  <div style="max-height=400px;overflow-y:auto;">
    <b>Groups (<?php echo $count; ?>): </b><br />
  <?php
  foreach ($entries[0]['memberof'] as $key => $value) {
    if ($key === "count") {
    } else {
      $exKey = explode(',', $value);
      $group = preg_replace("/CN=/","",$exKey[0]);
      array_push($groupList, $group);
      echo $group . "<br />";
    }
  }
  ?>
  </div>

  <?php
  // Check new array of groups for matching group
  if (in_array($groupName, $groupList)) {
    ?>
    <div>
      Found it: <?php echo $groupName; ?>
    </div>
    <?php
  }

// If bind fails
} else {
  ?>
  <div>
  User lookup fail. Incorrect password?
  </div>
  <?php
}
?>
</body>
</html>
