<?php

/*
**************************************************************************************************************************
** CORAL Licenses Module v. 1.2
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/

class LicenseN extends DatabaseObject {

  protected function defineRelationships() {}

  protected function defineIsbnOrIssn() {}

  protected function overridePrimaryKeyName() {}

    public function asArray() {
    $rarray = array();
    foreach (array_keys($this->attributeNames) as $attributeName) {
      if ($this->$attributeName != null) {
        $rarray[$attributeName] = $this->$attributeName;
      }
    }

        $status = new Status(new NamedArguments(array('primaryKey' => $this->statusID)));
        $rarray['status'] = $status->shortName;

        if ($this->licenseTypeID) {
            $licenseType = new LicenseType(new NamedArguments(array('primaryKey' => $this->licenseTypeID)));
            $rarray['licenseType'] = $licenseType->shortName;
        }

        if ($this->licenseFormatID) {
            $licenseFormat = new LicenseFormat(new NamedArguments(array('primaryKey' => $this->licenseFormatID)));
            $rarray['licenseFormat'] = $licenseFormat->shortName;
        }

        if ($this->acquisitionTypeID) {
            $acquisitionType = new AcquisitionType(new NamedArguments(array('primaryKey' => $this->acquisitionTypeID)));
            $rarray['acquisitionType'] = $acquisitionType->shortName;
        }

    $identifiers = $this->getIsbnOrIssn();
    $rarray['isbnOrIssn'] = array();
    foreach ($identifiers as $identifier) {
        array_push($rarray['isbnOrIssn'], $identifier->isbnOrIssn);
    }

        $aliases = $this->getAliases();
        $rarray['aliases'] = array();
    foreach ($aliases as $alias) {
        array_push($rarray['aliases'], $alias->shortName);
    }

    return $rarray;


    }

  //returns license objects by title
    public function getLicenseByTitle($title) {

        $query = "SELECT *
      FROM License
      WHERE UPPER(titleText) = '" . str_replace("'", "''", strtoupper($title)) . "'
      ORDER BY 1";

        $result = $this->db->processQuery($query, 'assoc');

        $objects = array();

        //need to do this since it could be that there's only one request and this is how the dbservice returns result
        if (isset($result['licenseID'])) { $result = [$result]; }
        foreach ($result as $row) {
            $object = new LicenseN(new NamedArguments(array('primaryKey' => $row['licenseID'])));
            array_push($objects, $object);
        }

        return $objects;
    }

    //returns license object by ebscoKbId
    public function getLicenseByEbscoKbId($ebscoKbId) {

        $query = "SELECT *
      FROM License
      WHERE ebscoKbID = $ebscoKbId
      LIMIT 0,1";

        $result = $this->db->processQuery($query, 'assoc');

        if (isset($result['licenseID'])) {
          return new LicenseN(new NamedArguments(['primaryKey' => $result['licenseID']]));
    } else {
          return false;
    }
    }

    public function getLicenseAcquisitions() {
        $query = "SELECT * from LicenseAcquisition WHERE licenseID = " . $this->licenseID . " ORDER BY subscriptionStartDate DESC, subscriptionEndDate DESC";
    $result = $this->db->processQuery($query, 'assoc');
        $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['licenseAcquisitionID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $object = new LicenseAcquisition(new NamedArguments(array('primaryKey' => $row['licenseAcquisitionID'])));
      array_push($objects, $object);
    }
    return $objects;

    }

    public function countLicenseAcquisitions() {
        $query = "SELECT COUNT(*) AS count FROM LicenseAcquisition WHERE licenseID = " . $this->licenseID;
    $result = $this->db->processQuery($query, 'assoc');
        return ($result) ? $result['count'] : 0;
    }

  //returns license objects by title
  public function getLicenseByIsbnOrISSN($isbnOrISSN) {
    $query = "SELECT DISTINCT(licenseID)
      FROM IsbnOrIssn";

    $i = 0;

    if (!is_array($isbnOrISSN)) {
      if ($isbnOrISSN === null) return;
      $value = $isbnOrISSN;
      $isbnOrISSN = array($value);
    }

    foreach ($isbnOrISSN as $value) {
      $query .= ($i == 0) ? " WHERE " : " OR ";
      $query .= "isbnOrIssn = '" . $this->db->escapeString($value) . "'";
      $i++;
    }

    $query .=  " ORDER BY 1";

    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['licenseID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $object = new LicenseN(new NamedArguments(array('primaryKey' => $row['licenseID'])));
      array_push($objects, $object);
    }

    return $objects;
  }



  public function getIsbnOrIssn() {
    $query = "SELECT *
      FROM IsbnOrIssn
      WHERE licenseID = '" . $this->licenseID . "'
      ORDER BY 1";

    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['isbnOrIssnID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $object = new IsbnOrIssn(new NamedArguments(array('primaryKey' => $row['isbnOrIssnID'])));
      array_push($objects, $object);
    }

    return $objects;
  }



  //returns array of parent license objects
  public function getParentLicenses() {
    return $this->getRelatedLicenses('licenseID');
  }



  //returns array of child license objects
  public function getChildLicenses() {
    return $this->getRelatedLicenses('relatedlicenseID');
  }



    // return array of related license objects
    private function getRelatedLicenses($key) {
        $query = "SELECT rr.licenseRelationshipID
      FROM LicenseRelationship rr
            JOIN License r on rr.licenseID = r.licenseID
      WHERE rr.$key = '" . $this->licenseID . "'
      AND relationshipTypeID = '1'
      ORDER BY r.shortName";

        $result = $this->db->processQuery($query, 'assoc');

        $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['licenseRelationshipID'])) {
      $object = new LicenseRelationship(new NamedArguments(array('primaryKey' => $result['licenseRelationshipID'])));
      array_push($objects, $object);
    }else{
      $db = DBService::getInstance();
      foreach ($result as $row) {
        $object = new LicenseRelationship(new NamedArguments(array('primaryKey' => $row['licenseRelationshipID'],'db'=>$db)));
        array_push($objects, $object);
      }
    }

        return $objects;

    }


  //deletes all parent licenses associated with this license
  public function removeParentLicenses() {

    $query = "DELETE FROM LicenseRelationship WHERE licenseID = '" . $this->licenseID . "'";

    return $this->db->processQuery($query);
  }



  //returns array of alias objects
  public function getAliases() {

    $query = "SELECT * FROM Alias WHERE licenseID = '" . $this->licenseID . "' order by shortName";

    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['aliasID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $object = new Alias(new NamedArguments(array('primaryKey' => $row['aliasID'])));
      array_push($objects, $object);
    }

    return $objects;
  }


  //returns array of contact objects
  public function getCreatorsArray() {

    $creatorsArray = array();

    //get license specific creators
    $query = "SELECT distinct loginID, firstName, lastName
      FROM License R, User U
      WHERE U.loginID = R.createLoginID
      ORDER BY lastName, firstName, loginID";

    $result = $this->db->processQuery($query, 'assoc');

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['loginID'])){ $result = [$result] ;}
    foreach ($result as $row) {
      array_push($creatorsArray, $row);
    }

    return $creatorsArray;
  }



  //returns array of external login records
  public function getExternalLoginArray() {

    $config = new Configuration;
    $elArray = array();

    //get license specific accounts first
    $query = "SELECT EL.*,  ELT.shortName externalLoginType
        FROM ExternalLogin EL, ExternalLoginType ELT
        WHERE EL.externalLoginTypeID = ELT.externalLoginTypeID
        AND licenseID = '" . $this->licenseID . "'
        ORDER BY ELT.shortName;";

    $result = $this->db->processQuery($query, 'assoc');

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['externalLoginID'])){ $result = [$result]; }
    foreach ($result as $row) {
      array_push($elArray, $row);
    }

    //if the org module is installed also get the external logins from org database
    if ($config->settings->organizationsModule == 'Y') {
      $dbName = $config->settings->organizationsDatabaseName;

      $query = "SELECT DISTINCT EL.*, ELT.shortName externalLoginType, O.name organizationName
            FROM " . $dbName . ".ExternalLogin EL, " . $dbName . ".ExternalLoginType ELT, " . $dbName . ".Organization O,
              License R, LicenseOrganizationLink ROL
            WHERE EL.externalLoginTypeID = ELT.externalLoginTypeID
            AND R.licenseID = ROL.licenseID
            AND ROL.organizationID = EL.organizationID
            AND O.organizationID = EL.organizationID
            AND R.licenseID = '" . $this->licenseID . "'
            ORDER BY ELT.shortName;";

      $result = $this->db->processQuery($query, 'assoc');

      //need to do this since it could be that there's only one request and this is how the dbservice returns result
      if (isset($result['externalLoginID'])){ $result = [$result]; }
      foreach ($result as $row) {
        array_push($elArray, $row);
      }

    }
    return $elArray;
  }



  //returns array of notes objects
  public function getNotes($tabName = NULL) {

    if ($tabName) {
      $query = "SELECT * FROM LicenseNote RN
            WHERE entityID = '" . $this->licenseID . "'
            AND UPPER(tabName) = UPPER('" . $tabName . "')
            ORDER BY updateDate desc";
    }else{
      $query = "SELECT RN.*
            FROM LicenseNote RN
            LEFT JOIN NoteType NT ON NT.noteTypeID = RN.noteTypeID
            WHERE entityID = '" . $this->licenseID . "'
            ORDER BY updateDate desc, NT.shortName";
    }

    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['licenseNoteID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $object = new LicenseNote(new NamedArguments(array('primaryKey' => $row['licenseNoteID'])));
      array_push($objects, $object);
    }

    return $objects;
  }



  //returns array of the initial note object
  public function getInitialNote() {
    $noteType = new NoteType();

    $query = "SELECT * FROM LicenseNote RN
          WHERE entityID = '" . $this->licenseID . "'
          AND noteTypeID = '" . $noteType->getInitialNoteTypeID() . "'
          ORDER BY noteTypeID desc LIMIT 0,1";


    $result = $this->db->processQuery($query, 'assoc');

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['licenseNoteID'])) {
      return new LicenseNote(new NamedArguments(array('primaryKey' => $result['licenseNoteID'])));
    } else{
      return new LicenseNote();
    }
  }



  public function getExportableIssues($archivedOnly=false){
    if ($this->db->config->settings->organizationsModule == 'Y' && $this->db->config->settings->organizationsDatabaseName) {
      $contactsDB = $this->db->config->settings->organizationsDatabaseName;
    } else {
      $contactsDB = $this->db->config->database->name;
    }

    $query = "SELECT i.*,(SELECT GROUP_CONCAT(CONCAT(sc.name,' - ',sc.emailAddress) SEPARATOR ', ')
                FROM IssueContact sic
                LEFT JOIN `{$contactsDB}`.Contact sc ON sc.contactID=sic.contactID
                WHERE sic.issueID=i.issueID) AS `contacts`,
               (SELECT GROUP_CONCAT(se.titleText SEPARATOR ', ')
                FROM IssueRelationship sir
                LEFT JOIN License se ON (se.licenseID=sir.entityID AND sir.entityTypeID=2)
                WHERE sir.issueID=i.issueID) AS `appliesto`,
               (SELECT GROUP_CONCAT(sie.email SEPARATOR ', ')
                FROM IssueEmail sie
                WHERE sie.issueID=i.issueID) AS `CCs`
          FROM Issue i
          LEFT JOIN IssueRelationship ir ON ir.issueID=i.issueID
          WHERE ir.entityID='{$this->licenseID}' AND ir.entityTypeID=2";
    if ($archivedOnly) {
      $query .= " AND i.dateClosed IS NOT NULL";
    } else {
      $query .= " AND i.dateClosed IS NULL";
    }
    $query .= "  ORDER BY i.dateCreated DESC";

    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['issueID'])) {
      return array($result);
    }else{
      return $result;
    }
  }





  public function getExportableDowntimes($archivedOnly=false){
    $result = $this->getDownTimeResults($archivedOnly);

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['downtimeID'])) {
      return array($result);
    }else{
      return $result;
    }
  }


  //returns array of externalLogin objects
  public function getExternalLogins() {

    $query = "SELECT * FROM ExternalLogin
          WHERE licenseID = '" . $this->licenseID . "'";

    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['externalLoginID'])){ $result = [$result]; }
    foreach ($result as $row) {
      $object = new ExternalLogin(new NamedArguments(array('primaryKey' => $row['externalLoginID'])));
      array_push($objects, $object);
    }

    return $objects;
  }



  public static function setSearch($search) {
  $config = new Configuration;
  echo var_dump($search);
    echo "we get into setSeach at some point$ ".$config->settings->defaultsort."$\n";
    if ($config->settings->defaultsort) {
      $orderBy = $config->settings->defaultsort;
    } else {
      $orderBy = "R.createDate DESC, TRIM(LEADING 'THE ' FROM UPPER(R.shortName)) asc";
    }
    $defaultSearchParameters = array(
    "orderBy" => $orderBy,
    "page" => 1,
    "recordsPerPage" => 25,
    );
    foreach ($defaultSearchParameters as $key => $value) {
      if (!isset($search[$key])) {
        $search[$key] = $value;
      }
    }
    foreach ($search as $key => $value) {
      $search[$key] = trim($value);
    }
    CoralSession::set('licenseSearch', $search);
  }



  public static function resetSearch() {
    LicenseN::setSearch(array());
  }



  public static function getSearch() {
    echo "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\n";
    if (!CoralSession::get('licenseSearch')) {
      echo "the search does get set#################################################\n";
      LicenseN::resetSearch();
    }
    echo "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\n";
    return CoralSession::get('licenseSearch');
  }



  public static function getSearchDetails() {
    // A successful mysqli_connect must be run before mysqli_real_escape_string will function.  Instantiating a license model will set up the connection
    $license = new LicenseN();

    $search = LicenseN::getSearch();

    echo "here is the search thing: ".$search["shortName"]."\n";
    $whereAdd = array();
    $searchDisplay = array();
    $config = new Configuration();


    //if name is passed in also search alias, organizations and organization aliases
    echo "here is the if statement".$search['shortName']."\n";
    if ($search['shortName']) {
      $nameQueryString = $license->db->escapeString(strtoupper($search['shortName']));
      $nameQueryString = preg_replace("/ +/", "%", $nameQueryString);
      $nameQueryString = "'%" . $nameQueryString . "%'";

      if ($config->settings->organizationsModule == 'Y') {
        $dbName = $config->settings->organizationsDatabaseName;

        $whereAdd[] = "((UPPER(R.shortName) LIKE " . $nameQueryString . ") OR (UPPER(A.shortName) LIKE " . $nameQueryString . ") OR (UPPER(O.name) LIKE " . $nameQueryString . ") OR (UPPER(OA.name) LIKE " . $nameQueryString . ") OR (UPPER(RP.titleText) LIKE " . $nameQueryString . ") OR (UPPER(RC.titleText) LIKE " . $nameQueryString . ") OR (UPPER(RA.recordSetIdentifier) LIKE " . $nameQueryString . "))";

      }else{

        $whereAdd[] = "((UPPER(R.shortName) LIKE " . $nameQueryString . ") OR (UPPER(A.shortName) LIKE " . $nameQueryString . ") OR (UPPER(O.shortName) LIKE " . $nameQueryString . ") OR (UPPER(RP.titleText) LIKE " . $nameQueryString . ") OR (UPPER(RC.titleText) LIKE " . $nameQueryString . ") OR (UPPER(RA.recordSetIdentifier) LIKE " . $nameQueryString . "))";

      }
      echo "here is the whereadd: ".$whereAdd."\n";
      $searchDisplay[] = _("Name contains: ") . $search['shortName'];
    }

    //get where statements together (and escape single quotes)
    if ($search['licenseISBNOrISSN']) {
      $licenseISBNOrISSN = $license->db->escapeString(str_replace("-","",$search['licenseISBNOrISSN']));
      $whereAdd[] = "REPLACE(I.isbnOrIssn,'-','') = '" . $licenseISBNOrISSN . "'";
      $searchDisplay[] = _("ISSN/ISBN: ") . $search['licenseISBNOrISSN'];
    }

    if ($search['stepName']) {
      $status = new Status();
      $completedStatusID = $status->getIDFromName('complete');
      $whereAdd[] = "(R.statusID != $completedStatusID AND RS.stepName = '" . $license->db->escapeString($search['stepName']) . "' AND RS.stepStartDate IS NOT NULL AND RS.stepEndDate IS NULL)";
      $searchDisplay[] = _("Workflow Step: ") . $search['stepName'];
    }


    if ($search['parent'] != null) {
      $relationship_options = ["RRC", "RRP"];
      if (!in_array($search['parent'], $relationship_options)) {
        throw new Exception("Aborting query, because of potential sql injection. Relationship types can be RRC (child) or RRP (parent).");
        exit();
      }
      $parentadd = "(" . $search['parent'] . ".relationshipTypeID = 1";
      $parentadd .= ")";
      $whereAdd[] = $parentadd;
    }



    if ($search['statusID']) {
      $whereAdd[] = "R.statusID = '" . $license->db->escapeString($search['statusID']) . "'";
      $status = new Status(new NamedArguments(array('primaryKey' => $search['statusID'])));
      $searchDisplay[] = _("Status: ") . $status->shortName;
    }

    if ($search['creatorLoginID']) {
      $whereAdd[] = "R.createLoginID = '" . $license->db->escapeString($search['creatorLoginID']) . "'";

      $createUser = new User(new NamedArguments(array('primaryKey' => $search['creatorLoginID'])));
      if ($createUser->firstName) {
        $name = $createUser->lastName . ", " . $createUser->firstName;
      }else{
        $name = $createUser->loginID;
      }
      $searchDisplay[] = _("Creator: ") . $name;
    }

    if ($search['licenseFormatID']) {
      $whereAdd[] = "R.licenseFormatID = '" . $license->db->escapeString($search['licenseFormatID']) . "'";
      $licenseFormat = new LicenseFormat(new NamedArguments(array('primaryKey' => $search['licenseFormatID'])));
      $searchDisplay[] = _("License Format: ") . $licenseFormat->shortName;
    }

    if ($search['acquisitionTypeID']) {
      $whereAdd[] = "RA.acquisitionTypeID = '" . $license->db->escapeString($search['acquisitionTypeID']) . "'";
      $acquisitionType = new AcquisitionType(new NamedArguments(array('primaryKey' => $search['acquisitionTypeID'])));
      $searchDisplay[] = _("Acquisition Type: ") . $acquisitionType->shortName;
    }


    if ($search['licenseNote']) {
      $whereAdd[] = "(UPPER(RNA.noteText) LIKE UPPER('%" . $license->db->escapeString($search['licenseNote']) . "%') AND RNA.tabName <> 'Product') OR (UPPER(RNR.noteText) LIKE UPPER('%" . $license->db->escapeString($search['licenseNote']) . "%') AND RNR.tabName = 'Product')";
      $searchDisplay[] = _("Note contains: ") . $search['licenseNote'];
    }

    if ($search['createDateStart']) {
      $whereAdd[] = "R.createDate >= STR_TO_DATE('" . $license->db->escapeString($search['createDateStart']) . "','%m/%d/%Y')";
      if (!$search['createDateEnd']) {
        $searchDisplay[] = _("Created on or after: ") . $search['createDateStart'];
      } else {
        $searchDisplay[] = _("Created between: ") . $search['createDateStart'] . " and " . $search['createDateEnd'];
      }
    }

    if ($search['createDateEnd']) {
      $whereAdd[] = "R.createDate <= STR_TO_DATE('" . $license->db->escapeString($search['createDateEnd']) . "','%m/%d/%Y')";
      if (!$search['createDateStart']) {
        $searchDisplay[] = _("Created on or before: ") . $search['createDateEnd'];
      }
    }

    if ($search['startWith']) {
      $whereAdd[] = "TRIM(LEADING 'THE ' FROM UPPER(R.shortName)) LIKE UPPER('" . $license->db->escapeString($search['startWith']) . "%')";
      $searchDisplay[] = _("Starts with: ") . $search['startWith'];
    }

    //the following are not-required fields with dropdowns and have "none" as an option
    if ($search['fund'] == 'none') {
      $whereAdd[] = "((RPAY.fundID IS NULL) OR (RPAY.fundID = '0'))";
      $searchDisplay[] = _("Fund: none");
    }else if ($search['fund']) {
      $fund = str_replace("-","",$search['fund']);
      $whereAdd[] = "RPAY.fundID = '" . $license->db->escapeString($fund) . "'";
      $searchDisplay[] = _("Fund: ") . $search['fund'];
    }
    if ($search['licenseTypeID'] == 'none') {
      $whereAdd[] = "((R.licenseTypeID IS NULL) OR (R.licenseTypeID = '0'))";
      $searchDisplay[] = _("License Type: none");
    }else if ($search['licenseTypeID']) {
      $whereAdd[] = "R.licenseTypeID = '" . $license->db->escapeString($search['licenseTypeID']) . "'";
      $licenseType = new LicenseType(new NamedArguments(array('primaryKey' => $search['licenseTypeID'])));
      $searchDisplay[] = _("License Type: ") . $licenseType->shortName;
    }


    if ($search['generalSubjectID'] == 'none') {
      $whereAdd[] = "((GDLINK.generalSubjectID IS NULL) OR (GDLINK.generalSubjectID = '0'))";
      $searchDisplay[] = _("License Type: none");
    }else if ($search['generalSubjectID']) {
      $whereAdd[] = "GDLINK.generalSubjectID = '" . $license->db->escapeString($search['generalSubjectID']) . "'";
      $generalSubject = new GeneralSubject(new NamedArguments(array('primaryKey' => $search['generalSubjectID'])));
      $searchDisplay[] = _("General Subject: ") . $generalSubject->shortName;
    }

    if ($search['detailedSubjectID'] == 'none') {
      $whereAdd[] = "((GDLINK.detailedSubjectID IS NULL) OR (GDLINK.detailedSubjectID = '0') OR (GDLINK.detailedSubjectID = '-1'))";
      $searchDisplay[] = _("License Type: none");
    }else if ($search['detailedSubjectID']) {
      $whereAdd[] = "GDLINK.detailedSubjectID = '" . $license->db->escapeString($search['detailedSubjectID']) . "'";
      $detailedSubject = new DetailedSubject(new NamedArguments(array('primaryKey' => $search['detailedSubjectID'])));
      $searchDisplay[] = _("Detailed Subject: ") . $detailedSubject->shortName;
    }

    if ($search['noteTypeID'] == 'none') {
      $whereAdd[] = "(RNA.noteTypeID IS NULL) AND (RNA.noteText IS NOT NULL) AND (RNR.noteTypeID IS NULL) AND (RNR.noteText IS NOT NULL)";
      $searchDisplay[] = _("Note Type: none");
    }else if ($search['noteTypeID']) {
      $whereAdd[] = "((RNA.noteTypeID = '" . $license->db->escapeString($search['noteTypeID']) . "' AND RNA.tabName <> 'Product') OR (RNR.noteTypeID = '" . $license->db->escapeString($search['noteTypeID']) . "' AND RNR.tabName = 'Product'))";
      $noteType = new NoteType(new NamedArguments(array('primaryKey' => $search['noteTypeID'])));
      $searchDisplay[] = _("Note Type: ") . $noteType->shortName;
    }


    if ($search['purchaseSiteID'] == 'none') {
      $whereAdd[] = "RPSL.purchaseSiteID IS NULL";
      $searchDisplay[] = _("Purchase Site: none");
    }else if ($search['purchaseSiteID']) {
      $whereAdd[] = "RPSL.purchaseSiteID = '" . $license->db->escapeString($search['purchaseSiteID']) . "'";
      $purchaseSite = new PurchaseSite(new NamedArguments(array('primaryKey' => $search['purchaseSiteID'])));
      $searchDisplay[] = _("Purchase Site: ") . $purchaseSite->shortName;
    }


    if ($search['authorizedSiteID'] == 'none') {
      $whereAdd[] = "RAUSL.authorizedSiteID IS NULL";
      $searchDisplay[] = _("Authorized Site: none");
    }else if ($search['authorizedSiteID']) {
      $whereAdd[] = "RAUSL.authorizedSiteID = '" . $license->db->escapeString($search['authorizedSiteID']) . "'";
      $authorizedSite = new AuthorizedSite(new NamedArguments(array('primaryKey' => $search['authorizedSiteID'])));
      $searchDisplay[] = _("Authorized Site: ") . $authorizedSite->shortName;
    }


    if ($search['administeringSiteID'] == 'none') {
      $whereAdd[] = "RADSL.administeringSiteID IS NULL";
      $searchDisplay[] = _("Administering Site: none");
    }else if ($search['administeringSiteID']) {
      $whereAdd[] = "RADSL.administeringSiteID = '" . $license->db->escapeString($search['administeringSiteID']) . "'";
      $administeringSite = new AdministeringSite(new NamedArguments(array('primaryKey' => $search['administeringSiteID'])));
      $searchDisplay[] = _("Administering Site: ") . $administeringSite->shortName;
    }


    if ($search['authenticationTypeID'] == 'none') {
      $whereAdd[] = "RA.authenticationTypeID IS NULL";
      $searchDisplay[] = _("Authentication Type: none");
    }else if ($search['authenticationTypeID']) {
      $whereAdd[] = "RA.authenticationTypeID = '" . $license->db->escapeString($search['authenticationTypeID']) . "'";
      $authenticationType = new AuthenticationType(new NamedArguments(array('primaryKey' => $search['authenticationTypeID'])));
      $searchDisplay[] = _("Authentication Type: ") . $authenticationType->shortName;
    }

    if ($search['catalogingStatusID'] == 'none') {
      $whereAdd[] = "(RA.catalogingStatusID IS NULL)";
      $searchDisplay[] = _("Cataloging Status: none");
    } else if ($search['catalogingStatusID']) {
      $whereAdd[] = "RA.catalogingStatusID = '" . $license->db->escapeString($search['catalogingStatusID']) . "'";
      $catalogingStatus = new CatalogingStatus(new NamedArguments(array('primaryKey' => $search['catalogingStatusID'])));
      $searchDisplay[] = _("Cataloging Status: ") . $catalogingStatus->shortName;
    }

    if ($search['publisher']) {
      $nameQueryString = $license->db->escapeString(strtoupper($search['publisher']));
      $nameQueryString = preg_replace("/ +/", "%", $nameQueryString);
        $nameQueryString = "'%" . $nameQueryString . "%'";
      if ($config->settings->organizationsModule == 'Y'){
        $dbName = $config->settings->organizationsDatabaseName;
        $whereAdd[] = "ROL.organizationRoleID=5 AND ((UPPER(O.name) LIKE " . $nameQueryString . ") OR (UPPER(OA.name) LIKE " . $nameQueryString . "))";
      }else{
        $whereAdd[] = "ROL.organizationRoleID=5 AND (UPPER(O.shortName) LIKE " . $nameQueryString . ")";
      }
      $searchDisplay[] = _("Publisher name contains: ") . $search['publisher'];
    }

    if ($search['platform']) {
      $nameQueryString = $license->db->escapeString(strtoupper($search['platform']));
      $nameQueryString = preg_replace("/ +/", "%", $nameQueryString);
      $nameQueryString = "'%" . $nameQueryString . "%'";
      if ($config->settings->organizationsModule == 'Y'){
        $dbName = $config->settings->organizationsDatabaseName;
        $whereAdd[] = "ROL.organizationRoleID=3 AND ((UPPER(O.name) LIKE " . $nameQueryString . ") OR (UPPER(OA.name) LIKE " . $nameQueryString . "))";
       }else{
         $whereAdd[] = "ROL.organizationRoleID=3 AND (UPPER(O.shortName) LIKE " . $nameQueryString . ")";
       }
       $searchDisplay[] = _("Platform name contains: ") . $search['publisher'];
     }

     if ($search['provider']) {
       $nameQueryString = $license->db->escapeString(strtoupper($search['provider']));
       $nameQueryString = preg_replace("/ +/", "%", $nameQueryString);
       $nameQueryString = "'%" . $nameQueryString . "%'";
       if ($config->settings->organizationsModule == 'Y'){
         $dbName = $config->settings->organizationsDatabaseName;
         $whereAdd[] = "ROL.organizationRoleID=4 AND ((UPPER(O.name) LIKE " . $nameQueryString . ") OR (UPPER(OA.name) LIKE " . $nameQueryString . "))";
       }else{
         $whereAdd[] = "ROL.organizationRoleID=4 AND (UPPER(O.shortName) LIKE " . $nameQueryString . ")";
       }
      $searchDisplay[] = _("Provider name contains: ") . $search['publisher'];
    }

    $orderBy = $search['orderBy'];


    $page = $search['page'];
    $recordsPerPage = $search['recordsPerPage'];
    return array("where" => $whereAdd, "page" => $page, "order" => $orderBy, "perPage" => $recordsPerPage, "display" => $searchDisplay);
  }



  public function searchQuery($whereAdd, $orderBy = '', $limit = '', $count = false) {
    $config = new Configuration();
    $status = new Status();

    if ($config->settings->organizationsModule == 'Y') {
      $dbName = $config->settings->organizationsDatabaseName;

      $orgJoinAdd = "
        LEFT JOIN LicenseOrganizationLink ROL ON R.licenseID = ROL.licenseID
        LEFT JOIN $dbName.Organization O ON O.organizationID = ROL.organizationID
        LEFT JOIN $dbName.Alias OA ON OA.organizationID = ROL.organizationID";

    }else{
      $orgJoinAdd = "
        LEFT JOIN LicenseOrganizationLink ROL ON R.licenseID = ROL.licenseID
        LEFT JOIN Organization O ON O.organizationID = ROL.organizationID";
    }

    $savedStatusID = intval($status->getIDFromName('saved'));
    //also add to not retrieve saved records
    $whereAdd[] = "R.statusID != " . $savedStatusID;

    if (count($whereAdd) > 0) {
      $whereStatement = "WHERE\n  " . implode(" AND ", $whereAdd);
    }else{
      $whereStatement = "";
    }

    if ($count) {
      $select = "SELECT COUNT(DISTINCT R.licenseID) count";
      $groupBy = "";
    } else {
      $select = "SELECT
                  R.licenseID,
                  R.shortName,
                  GROUP_CONCAT(DISTINCT AT.shortName SEPARATOR ' / ') as acquisitionType,
                  R.createLoginID,
                  CU.firstName,
                  CU.lastName,
                  R.createDate,
                  S.shortName status,
                  GROUP_CONCAT(DISTINCT A.shortName, I.isbnOrIssn ORDER BY A.shortName DESC SEPARATOR '<br />') aliases";
      $groupBy = "GROUP BY R.licenseID";
    }

    $referenced_tables = array();

    $table_matches = array();

    // Build a list of tables that are referenced by the select and where statements in order to limit the number of joins performed in the search.
    $table_matching_regex = "/\b[A-Z]+(?=[.][A-Z]+)/iu";
    preg_match_all($table_matching_regex, $select, $table_matches);
    $referenced_tables_select = array_unique($table_matches[0]);

    preg_match_all($table_matching_regex, $whereStatement, $table_matches);
    $referenced_tables = array_unique(array_merge($referenced_tables_select, $table_matches[0]));

    // These join statements will only be included in the query if the alias is referenced by the select and/or where.
    $conditional_joins = explode("\n", "LEFT JOIN LicenseFormat RF ON R.licenseFormatID = RF.licenseFormatID
                  LEFT JOIN LicenseType RT ON R.licenseTypeID = RT.licenseTypeID
                  LEFT JOIN AcquisitionType AT ON RA.acquisitionTypeID = AT.acquisitionTypeID
                  LEFT JOIN Status S ON R.statusID = S.statusID
                  LEFT JOIN User CU ON R.createLoginID = CU.loginID
                  LEFT JOIN LicensePurchaseSiteLink RPSL ON RA.licenseAcquisitionID = RPSL.licenseAcquisitionID
                  LEFT JOIN LicenseAuthorizedSiteLink RAUSL ON RA.licenseAcquisitionID = RAUSL.licenseAcquisitionID
                  LEFT JOIN LicenseAdministeringSiteLink RADSL ON RA.licenseAcquisitionID = RADSL.licenseAcquisitionID
                  LEFT JOIN LicenseNote RNA ON RA.licenseAcquisitionID = RNA.entityID
                  LEFT JOIN LicenseNote RNR ON R.licenseID = RNR.entityID
                  LEFT JOIN LicensePayment RPAY ON RA.licenseAcquisitionID = RPAY.licenseAcquisitionID
                  LEFT JOIN LicenseStep RS ON RA.licenseAcquisitionID = RS.licenseAcquisitionID
                  LEFT JOIN IsbnOrIssn I ON R.licenseID = I.licenseID
                  ");

    $additional_joins = array();
    foreach($conditional_joins as $join) {
      // drop the last line of $conditional_joins which is empty
      if (trim($join) == "") { break; }

      $match = array();
      preg_match("/[A-Z]+(?= ON )/i", $join, $match);
      $table_name = $match[0];
      if (in_array($table_name, $referenced_tables)) {
        $additional_joins[] = $join;
      }
    }

	// Technically this is not the most efficient solution:
	// e.g. if RRP is needed but R is not, this solution will still do both joins,
	// these seem like rare enough edge cases (which involve expected slowdown in
	// the query anyway) that it's not worth cluttering up this code further.
    $table_join_requirements = [
      [ // Organization Tables
        "required_tables" => ["ROL", "O", "OA"],
        "join_rule" => $orgJoinAdd
      ],
      [ // Subject Tables
        "required_tables" => ["RSUB", "GDLINK"],
        "join_rule" => "LEFT JOIN LicenseSubject RSUB ON R.licenseID = RSUB.licenseID
                        LEFT JOIN GeneralDetailSubjectLink GDLINK ON RSUB.generalDetailSubjectLinkID = GDLINK.generalDetailSubjectLinkID"
      ],
      [ // Related License PARENT Tables
        "required_tables" => ["RRP", "RP"],
        "join_rule" => "LEFT JOIN LicenseRelationship RRP ON RRP.licenseID = R.licenseID
                        LEFT JOIN License RP ON RP.licenseID = RRP.relatedlicenseID"
      ],
      [ // Related License CHILD Tables
        "required_tables" => ["RRC", "RC"],
        "join_rule" => "LEFT JOIN LicenseRelationship RRC ON RRC.relatedlicenseID = R.licenseID
                        LEFT JOIN License RC ON RC.licenseID = RRC.licenseID"
      ]
    ];
    foreach ($table_join_requirements as $table_join) {
      foreach ($table_join["required_tables"] as $table) {
        if (in_array($table, $referenced_tables)) {
          $additional_joins[] = $table_join["join_rule"];
          break;
        }
      }
    }

    $query = $select . "
                FROM License R
                  LEFT JOIN Alias A ON R.licenseID = A.licenseID
                  LEFT JOIN LicenseAcquisition RA ON R.licenseID = RA.licenseID
                  " . implode("\n", $additional_joins) . "
                  " . $whereStatement . "
                  " . $groupBy;

    if ($orderBy) {
      $query .= "\nORDER BY " . $orderBy;
    }

    if ($limit) {
      $query .= "\nLIMIT " . $limit;
    }
    return $query;
  }



  //returns array based on search
  public function search($whereAdd, $orderBy, $limit) {
    $query = $this->searchQuery($whereAdd, $orderBy, $limit, false);

    $result = $this->db->processQuery($query, 'assoc');

    $searchArray = array();

    //need to do this since it could be that there's only one result and this is how the dbservice returns result
    if (isset($result['licenseID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $row = static::addIdsToLicensesRow($row);
      array_push($searchArray, $row);
    }
    return $searchArray;
  }



  private static function addIdsToLicensesRow($row) {
    $license = new LicenseN(new NamedArguments(array('primaryKey' => $row['licenseID'])));
    $isbnOrIssns = $license->getIsbnOrIssn();
    $row['isbnOrIssns'] = [];
    foreach ($isbnOrIssns as $isbnOrIssn) {
      array_push($row['isbnOrIssns'], $isbnOrIssn->isbnOrIssn);
    }
    return $row;
  }



  public function searchCount($whereAdd) {
    $query = $this->searchQuery($whereAdd, '', '', true);
    $result = $this->db->processQuery($query, 'assoc');

    return $result['count'];
  }



  //used for A-Z on search (index)
  public function getAlphabeticalList() {
    $alphArray = array();
    $result = $this->db->query("SELECT DISTINCT UPPER(SUBSTR(TRIM(LEADING 'The ' FROM titleText),1,1)) letter, COUNT(SUBSTR(TRIM(LEADING 'The ' FROM titleText),1,1)) letter_count
                FROM License R
                GROUP BY UPPER(SUBSTR(TRIM(LEADING 'The ' FROM titleText),1,1))
                ORDER BY 1;");

    while ($row = $result->fetch_assoc()) {
      $alphArray[$row['letter']] = $row['letter_count'];
    }

    return $alphArray;
  }


/*
License.licenseID
License.shortName
License.createDate

License.consortiumID -> Consortium.shortName

License.statusID -> Status.shortName

License.organizationID -> Organization.shortName

License.licenseID -> Document.shortName
License.licenseID -> Document.effectiveDate
License.licenseID -> Document.expirationDate
License.licenseID -> Document.documentURL





*/
  //returns array based on search for excel output (export.php)
  public function export($whereAdd, $orderBy) {
    //$exportConfigObj = new ExportConfig();
    //$exportConfig = $exportConfigObj->getConfiguration();

    $distinct_license_id_query = "SELECT DISTINCT(licenseID) AS license_id FROM License;";
    $distinct_license_ids_assoc_array = $this->db->processQuery($distinct_license_id_query, 'assoc');
    $distinct_license_ids = array_map(function($value) {
      return $value["license_id"];
    }, $distinct_license_ids_assoc_array);

    $config = new Configuration();

    if ($config->settings->organizationsModule == 'Y') {
      $orgJoinAdd = "
  LEFT JOIN $dbName.Organization O ON O.organizationID = ROL.organizationID
  LEFT JOIN $dbName.Alias OA ON OA.organizationID = ROL.organizationID";
      $orgSelectAdd = "  GROUP_CONCAT(DISTINCT O.name ORDER BY O.name DESC SEPARATOR '; ') organizationNames,";
    }else{
      $orgJoinAdd = "  LEFT JOIN Organization O ON O.organizationID = ROL.organizationID";
      $orgSelectAdd = "  GROUP_CONCAT(DISTINCT O.shortName ORDER BY O.shortName DESC SEPARATOR '; ') organizationNames,";
    }


    $licSelectAdd = '';
    $licJoinAdd = '';
    if ($config->settings->licensingModule == 'Y') {
      $dbName = $config->settings->licensingDatabaseName;

      $licJoinAdd = "
  LEFT JOIN LicenseLicenseLink RLL ON RLL.licenseAcquisitionID = RA.licenseAcquisitionID
  LEFT JOIN $dbName.License L ON RLL.licenseID = L.licenseID
  LEFT JOIN LicenseLicenseStatus RLS ON RLS.licenseAcquisitionID = RA.licenseAcquisitionID
  LEFT JOIN LicenseStatus LS ON LS.licenseStatusID = RLS.licenseStatusID";
      $date_format_to_use = return_date_format();
      $licSelectAdd = "
  GROUP_CONCAT(DISTINCT L.shortName ORDER BY L.shortName DESC SEPARATOR '; ') licenseNames,
  GROUP_CONCAT(
    DISTINCT
      LS.shortName,
      ': ',
      DATE_FORMAT(RLS.licenseStatusChangeDate, '$date_format_to_use')
    ORDER BY RLS.licenseStatusChangeDate DESC SEPARATOR '; '
  ) licenseStatuses,";
    }


    $status = new Status();
    //also add to not retrieve saved records
    $savedStatusID = intval($status->getIDFromName('saved'));
    echo "this is get from saved: ".($status->getIDFromName('saved'))."\n";
    echo "this is saved stat".$savedStatusID."\n";
    $whereAdd[] = "R.statusID != " . $savedStatusID;
    // $whereAdd[] = "R.licenseID IN (LIST_OF_IDS)";

    $whereStatement = "WHERE " . implode(" AND ", $whereAdd);
    echo "here is the where statement".$whereStatement."\n";
    if (!empty(trim($orderBy))) {
      $orderBy = "ORDER BY $orderBy";
    }
    else {
      $orderBy = "";
    }

    //now actually execute query
    $query = "
SELECT
  R.licenseID,
  R.shortName,
  R.createDate,
  CON.shortName conName
  "./*ST.shortName,
  O.shortName,
  D.shortName,
  D.effectiveDate,
  D.expirationDate,
  D.documentURL
*/
"FROM License R
  LEFT JOIN Consortium CON ON CON.consortiumID = R.consortiumID";/*
  LEFT JOIN Status ST ON ST.statusID = R.statusID
  LEFT JOIN Organization O ON O.organizationID = R.organizationID
  LEFT JOIN Document D ON D.licenseID = R.licenseID

"$whereStatement"; 
/*
GROUP BY
  R.licenseID,
  R.shortName

  
";*/
/* $orderBy  
SELECT
  R.licenseID,
  R.shortName,
  AT.shortName acquisitionType,
  CONCAT_WS(' ', CU.firstName, CU.lastName) createName,
  R.createDate createDate,
  CONCAT_WS(' ', UU.firstName, UU.lastName) updateName,
  R.updateDate updateDate,
  S.shortName status,
  RT.shortName licenseType,
  RF.shortName licenseFormat,
  RA.orderNumber,
  RA.systemNumber,
  R.licenseURL,
  R.licenseAltURL,
  RA.subscriptionStartDate,
  RA.subscriptionEndDate,
  RA.subscriptionAlertEnabledInd,
  AUT.shortName authenticationType,
  AM.shortName accessMethod,
  SL.shortName storageLocation,
  UL.shortName userLimit,
  RA.authenticationUserName,
  RA.authenticationPassword,
  RA.coverageText,
  CT.shortName catalogingType,
  CS.shortName catalogingStatus,
  RA.recordSetIdentifier,
  RA.bibSourceURL,
  RA.numberRecordsAvailable,
  RA.numberRecordsLoaded,
  RA.hasOclcHoldings,
  GROUP_CONCAT(DISTINCT I.isbnOrIssn ORDER BY isbnOrIssnID SEPARATOR '; ') AS isbnOrIssn,
  RPAY.year,
  F.shortName as fundName, F.fundCode,
  ROUND(COALESCE(RPAY.priceTaxExcluded, 0) / 100, 2) as priceTaxExcluded,
  ROUND(COALESCE(RPAY.taxRate, 0) / 100, 2) as taxRate,
  ROUND(COALESCE(RPAY.priceTaxIncluded, 0) / 100, 2) as priceTaxIncluded,
  ROUND(COALESCE(RPAY.paymentAmount, 0) / 100, 2) as paymentAmount,
  RPAY.currencyCode,
  CD.shortName as costDetails,
  OT.shortName as orderType,
  RPAY.costNote,
  RPAY.invoiceNum,
$orgSelectAdd
$licSelectAdd
$notesSelectAdd
$parentLicensesSelectAdd
$childLicensesSelectAdd
  GROUP_CONCAT(DISTINCT A.shortName ORDER BY A.shortName DESC SEPARATOR '; ') aliases,
  GROUP_CONCAT(DISTINCT PS.shortName ORDER BY PS.shortName DESC SEPARATOR '; ') purchasingSites,
  GROUP_CONCAT(DISTINCT AUS.shortName ORDER BY AUS.shortName DESC SEPARATOR '; ') authorizedSites,
  GROUP_CONCAT(DISTINCT ADS.shortName ORDER BY ADS.shortName DESC SEPARATOR '; ') administeringSites

FROM License R
  LEFT JOIN LicenseAcquisition RA ON RA.licenseID = R.licenseID
  LEFT JOIN LicensePayment RPAY ON RA.licenseAcquisitionID = RPAY.licenseAcquisitionID
  LEFT JOIN Alias A ON R.licenseID = A.licenseID
  LEFT JOIN LicenseOrganizationLink ROL ON R.licenseID = ROL.licenseID
$orgJoinAdd
$parentLicensesJoinAdd
$childLicensesJoinAdd
  LEFT JOIN LicenseSubject RSUB ON R.licenseID = RSUB.licenseID
  LEFT JOIN GeneralDetailSubjectLink GDLINK ON RSUB.generalDetailSubjectLinkID = GDLINK.generalDetailSubjectLinkID
  LEFT JOIN LicenseFormat RF ON R.licenseFormatID = RF.licenseFormatID
  LEFT JOIN LicenseType RT ON R.licenseTypeID = RT.licenseTypeID
  LEFT JOIN AcquisitionType AT ON RA.acquisitionTypeID = AT.acquisitionTypeID
  LEFT JOIN LicenseStep RS ON RA.licenseAcquisitionID = RS.licenseAcquisitionID
  LEFT JOIN Fund F ON RPAY.fundID = F.fundID
  LEFT JOIN OrderType OT ON RPAY.orderTypeID = OT.orderTypeID
  LEFT JOIN CostDetails CD ON RPAY.costDetailsID = CD.costDetailsID
  LEFT JOIN Status S ON R.statusID = S.statusID
  LEFT JOIN LicenseNote RN ON R.licenseID = RN.entityID
  LEFT JOIN NoteType NT ON RN.noteTypeID = NT.noteTypeID
  LEFT JOIN User CU ON R.createLoginID = CU.loginID
  LEFT JOIN User UU ON R.updateLoginID = UU.loginID
  LEFT JOIN CatalogingStatus CS ON RA.CatalogingStatusID = CS.catalogingStatusID
  LEFT JOIN CatalogingType CT ON RA.catalogingTypeID = CT.catalogingTypeID
  LEFT JOIN LicensePurchaseSiteLink RPSL ON RA.licenseAcquisitionID = RPSL.licenseAcquisitionID
  LEFT JOIN PurchaseSite PS ON RPSL.purchaseSiteID = PS.purchaseSiteID
  LEFT JOIN LicenseAuthorizedSiteLink RAUSL ON RA.licenseAcquisitionID = RAUSL.licenseAcquisitionID
  LEFT JOIN AuthorizedSite AUS ON RAUSL.authorizedSiteID = AUS.authorizedSiteID
  LEFT JOIN LicenseAdministeringSiteLink RADSL ON RA.licenseAcquisitionID = RADSL.licenseAcquisitionID
  LEFT JOIN AdministeringSite ADS ON RADSL.administeringSiteID = ADS.administeringSiteID
  LEFT JOIN AuthenticationType AUT ON AUT.authenticationTypeID = RA.authenticationTypeID
  LEFT JOIN AccessMethod AM ON AM.accessMethodID = RA.accessMethodID
  LEFT JOIN StorageLocation SL ON SL.storageLocationID = RA.storageLocationID
  LEFT JOIN UserLimit UL ON UL.userLimitID = RA.userLimitID
  LEFT JOIN IsbnOrIssn I ON I.licenseID = R.licenseID
$licJoinAdd
$notesJoinAdd
$joinsForTablesInWhere

$whereStatement

GROUP BY
  RPAY.licensePaymentID,
  R.licenseID,
  AT.shortName,
  RA.orderNumber,
  RA.systemNumber,
  RA.subscriptionAlertEnabledInd,
  AUT.shortName,
  AM.shortName,
  SL.shortName,
  UL.shortName,
  RA.authenticationUserName,
  RA.authenticationPassword,
  RA.coverageText,
  CT.shortName,
  CS.shortName,
  RA.recordSetIdentifier,
  RA.bibSourceURL,
  RA.numberRecordsAvailable,
  RA.numberRecordsLoaded,
  RA.hasOclcHoldings

$orderBy
";*/

    // This was determined by trial and error
    $CHUNK_SIZE = 10000;

    $searchArray = [];
    $slice_offset = 0;
    $license_id_chunk = array_slice($distinct_license_ids, $slice_offset, $CHUNK_SIZE);
    while (count($license_id_chunk) > 0) {

      $list_of_ids = implode(",", $license_id_chunk);
      $chunked_query = str_replace("LIST_OF_IDS",$list_of_ids,$query);
      $result = $this->db->processQuery(stripslashes($chunked_query), 'assoc');
      //need to do this since it could be that there's only one result and this is how the dbservice returns result
      if (isset($result['licenseID'])) {
        $result = [$result];
      }
      foreach ($result as $row) {
        array_push($searchArray, $row);
      }

      $slice_offset += $CHUNK_SIZE;
      $license_id_chunk = array_slice($distinct_license_ids, $slice_offset, $CHUNK_SIZE);
    }

    return $searchArray;
  }



  //search used index page drop down
  public function getOrganizationList() {
    $config = new Configuration;

    $orgArray = array();

    //if the org module is installed get the org names from org database
    if ($config->settings->organizationsModule == 'Y') {
      $dbName = $config->settings->organizationsDatabaseName;
      $query = "SELECT name, organizationID FROM " . $dbName . ".Organization ORDER BY 1;";

    //otherwise get the orgs from this database
    }else{
      $query = "SELECT shortName name, organizationID FROM Organization ORDER BY 1;";
    }


    $result = $this->db->processQuery($query, 'assoc');

    //need to do this since it could be that there's only one result and this is how the dbservice returns result
    if (isset($result['organizationID'])){ $result = [$result]; }
    foreach ($result as $row) {
      array_push($orgArray, $row);
    }

    return $orgArray;
  }



  //gets an array of organizations set up for this license (organizationID, organization, organizationRole)
  public function getOrganizationArray() {
    $config = new Configuration;

    //if the org module is installed get the org name from org database
    if ($config->settings->organizationsModule == 'Y') {
      $dbName = $config->settings->organizationsDatabaseName;

      $licenseOrgArray = array();

      $query = "SELECT * FROM LicenseOrganizationLink WHERE licenseID = '" . $this->licenseID . "'";

      $result = $this->db->processQuery($query, 'assoc');

      $objects = array();

      //need to do this since it could be that there's only one request and this is how the dbservice returns result
      if (isset($result['organizationID'])) {
        $orgArray = array();

        //first, get the organization name
        $query = "SELECT name FROM " . $dbName . ".Organization WHERE organizationID = " . $result['organizationID'];

        if ($orgResult = $this->db->query($query)) {
          while ($orgRow = $orgResult->fetch_assoc()) {
            $orgArray['organization'] = $orgRow['name'];
            $orgArray['organizationID'] = $result['organizationID'];
          }
        }

        //then, get the role name
        $query = "SELECT * FROM " . $dbName . ".OrganizationRole WHERE organizationRoleID = " . $result['organizationRoleID'];

        if ($orgResult = $this->db->query($query)) {
          while ($orgRow = $orgResult->fetch_assoc()) {
            $orgArray['organizationRoleID'] = $orgRow['organizationRoleID'];
            $orgArray['organizationRole'] = $orgRow['shortName'];
          }
        }

        array_push($licenseOrgArray, $orgArray);
      }else{
        foreach ($result as $row) {

          $orgArray = array();

          //first, get the organization name
          $query = "SELECT name FROM " . $dbName . ".Organization WHERE organizationID = " . $row['organizationID'];

          if ($orgResult = $this->db->query($query)) {
            while ($orgRow = $orgResult->fetch_assoc()) {
              $orgArray['organization'] = $orgRow['name'];
              $orgArray['organizationID'] = $row['organizationID'];
            }
          }

          //then, get the role name
          $query = "SELECT * FROM " . $dbName . ".OrganizationRole WHERE organizationRoleID = " . $row['organizationRoleID'];


          if ($orgResult = $this->db->query($query)) {
            while ($orgRow = $orgResult->fetch_assoc()) {
              $orgArray['organizationRoleID'] = $orgRow['organizationRoleID'];
              $orgArray['organizationRole'] = $orgRow['shortName'];
            }
          }

          array_push($licenseOrgArray, $orgArray);

        }

      }






    //otherwise if the org module is not installed get the org name from this database
    }else{



      $licenseOrgArray = array();

      $query = "SELECT * FROM LicenseOrganizationLink WHERE licenseID = '" . $this->licenseID . "'";

      $result = $this->db->processQuery($query, 'assoc');

      $objects = array();

      //need to do this since it could be that there's only one request and this is how the dbservice returns result
      if (isset($result['organizationID'])) {
        $orgArray = array();

        //first, get the organization name
        $query = "SELECT shortName FROM Organization WHERE organizationID = " . $result['organizationID'];

        if ($orgResult = $this->db->query($query)) {
          while ($orgRow = $orgResult->fetch_assoc()) {
            $orgArray['organization'] = $orgRow['shortName'];
            $orgArray['organizationID'] = $result['organizationID'];
          }
        }

        //then, get the role name
        $query = "SELECT * FROM OrganizationRole WHERE organizationRoleID = " . $result['organizationRoleID'];

        if ($orgResult = $this->db->query($query)) {
          while ($orgRow = $orgResult->fetch_assoc()) {
            $orgArray['organizationRoleID'] = $orgRow['organizationRoleID'];
            $orgArray['organizationRole'] = $orgRow['shortName'];
          }
        }

        array_push($licenseOrgArray, $orgArray);
      }else{
        foreach ($result as $row) {

          $orgArray = array();

          //first, get the organization name
          $query = "SELECT shortName FROM Organization WHERE organizationID = " . $row['organizationID'];

          if ($orgResult = $this->db->query($query)) {
            while ($orgRow = $orgResult->fetch_assoc()) {
              $orgArray['organization'] = $orgRow['shortName'];
              $orgArray['organizationID'] = $row['organizationID'];
            }
          }

          //then, get the role name
          $query = "SELECT * FROM OrganizationRole WHERE organizationRoleID = " . $row['organizationRoleID'];


          if ($orgResult = $this->db->query($query)) {
            while ($orgRow = $orgResult->fetch_assoc()) {
              $orgArray['organizationRoleID'] = $orgRow['organizationRoleID'];
              $orgArray['organizationRole'] = $orgRow['shortName'];
            }
          }

          array_push($licenseOrgArray, $orgArray);

        }

      }





    }


    return $licenseOrgArray;
  }



  public function getSiblingLicensesArray($organizationID) {

      $query = "SELECT DISTINCT r.licenseID, r. FROM LicenseOrganizationLink rol
            LEFT JOIN License r ON r.licenseID=rol.licenseID
            WHERE rol.organizationID=".$organizationID." AND r.archiveDate IS NULL
            ORDER BY r.shortName";

      $result = $this->db->processQuery($query, 'assoc');

      if (isset($result["licenseID"])) {
        return array($result);
      }

      return $result;
  }



  //gets an array of distinct organizations set up for this license (organizationID, organization)
  public function getDistinctOrganizationArray() {
    $config = new Configuration;

    //if the org module is installed get the org name from org database
    if ($config->settings->organizationsModule == 'Y') {
      $dbName = $config->settings->organizationsDatabaseName;

      $licenseOrgArray = array();

      $query = "SELECT DISTINCT organizationID FROM LicenseOrganizationLink WHERE licenseID = '" . $this->licenseID . "'";

      $result = $this->db->processQuery($query, 'assoc');

      $objects = array();

      //need to do this since it could be that there's only one request and this is how the dbservice returns result
      if (isset($result['organizationID'])) {
        $orgArray = array();

        //first, get the organization name
        $query = "SELECT name FROM " . $dbName . ".Organization WHERE organizationID = " . $result['organizationID'];

        if ($orgResult = $this->db->query($query)) {
          while ($orgRow = $orgResult->fetch_assoc()) {
            $orgArray['organization'] = $orgRow['name'];
            $orgArray['organizationID'] = $result['organizationID'];
          }
        }

        array_push($licenseOrgArray, $orgArray);
      }else{
        foreach ($result as $row) {

          $orgArray = array();

          //first, get the organization name
          $query = "SELECT DISTINCT name FROM " . $dbName . ".Organization WHERE organizationID = " . $row['organizationID'];

          if ($orgResult = $this->db->query($query)) {
            while ($orgRow = $orgResult->fetch_assoc()) {
              $orgArray['organization'] = $orgRow['name'];
              $orgArray['organizationID'] = $row['organizationID'];
            }
          }

          array_push($licenseOrgArray, $orgArray);

        }

      }






    //otherwise if the org module is not installed get the org name from this database
    }else{



      $licenseOrgArray = array();

      $query = "SELECT DISTINCT organizationID FROM LicenseOrganizationLink WHERE licenseID = '" . $this->licenseID . "'";

      $result = $this->db->processQuery($query, 'assoc');

      $objects = array();

      //need to do this since it could be that there's only one request and this is how the dbservice returns result
      if (isset($result['organizationID'])) {
        $orgArray = array();

        //first, get the organization name
        $query = "SELECT DISTINCT shortName FROM Organization WHERE organizationID = " . $result['organizationID'];

        if ($orgResult = $this->db->query($query)) {
          while ($orgRow = $orgResult->fetch_assoc()) {
            $orgArray['organization'] = $orgRow['shortName'];
            $orgArray['organizationID'] = $result['organizationID'];
          }
        }

        array_push($licenseOrgArray, $orgArray);
      }else{
        foreach ($result as $row) {

          $orgArray = array();

          //first, get the organization name
          $query = "SELECT DISTINCT shortName FROM Organization WHERE organizationID = " . $row['organizationID'];

          if ($orgResult = $this->db->query($query)) {
            while ($orgRow = $orgResult->fetch_assoc()) {
              $orgArray['organization'] = $orgRow['shortName'];
              $orgArray['organizationID'] = $row['organizationID'];
            }
          }

          array_push($licenseOrgArray, $orgArray);

        }

      }





    }


    return $licenseOrgArray;
  }


  //removes this license and its children
  public function removeLicenseAndChildren() {

    // for each children
    foreach ($this->getChildLicenses() as $instance) {
      $removeChild = true;
      $child = new LicenseN(new NamedArguments(array('primaryKey' => $instance->licenseID)));

      // get parents of this children
      $parents = $child->getParentLicenses();

      // If the child ressource belongs to another parent than the one we're removing
      foreach ($parents as $pinstance) {
        $parentLicenseObj = new License(new NamedArguments(array('primaryKey' => $pinstance->relatedlicenseID)));
        if ($parentLicenseObj->licenseID != $this->licenseID) {
          // We do not delete this child.
          $removeChild = false;
        }
      }
      if ($removeChild == true) {
        $child->removeLicense();
      }
    }
    // Finally, we remove the parent
    $this->removeLicense();
  }

    // Removes all license acquisitions from this license
    public function removeLicenseAcquisitions() {
        $instance = new LicenseAcquisition();
        foreach($this->getLicenseAcquisitions() as $instance) {
            $instance->removeLicenseAcquisition();
        }

    }


  //removes this license
  public function removeLicense() {
    //delete data from child linked tables
    $this->removeLicenseRelationships();
    $this->removeLicenseOrganizations();
    $this->removeAllSubjects();
    $this->removeAllIsbnOrIssn();
        $this->removeLicenseAcquisitions();
    $instance = new ExternalLogin();
    foreach ($this->getExternalLogins() as $instance) {
      $instance->delete();
    }

    $instance = new LicenseNote();
    foreach ($this->getNotes() as $instance) {
      $instance->delete();
    }

    $instance = new Alias();
    foreach ($this->getAliases() as $instance) {
      $instance->delete();
    }


    $this->delete();
  }



  //removes license hierarchy records
  public function removeLicenseRelationships() {

    $query = "DELETE
      FROM LicenseRelationship
      WHERE licenseID = '" . $this->licenseID . "' OR relatedlicenseID = '" . $this->licenseID . "'";

    $result = $this->db->processQuery($query);
  }


  //removes license organizations
  public function removeLicenseOrganizations() {

    $query = "DELETE
      FROM LicenseOrganizationLink
      WHERE licenseID = '" . $this->licenseID . "'";

    $result = $this->db->processQuery($query);
  }



  //removes license note records
  public function removeLicenseNotes() {

    $query = "DELETE
      FROM LicenseNote
      WHERE entityID = '" . $this->licenseID . "'";

    $result = $this->db->processQuery($query);
  }


  //search used for the license autocomplete
  public function licenseAutocomplete($q) {
    $licenseArray = array();
    $result = $this->db->query("SELECT titleText, licenseID
                FROM License
                WHERE upper(titleText) like upper('%" . $q . "%')
                ORDER BY 1;");

    while ($row = $result->fetch_assoc()) {
      $licenseArray[] = $row['titleText'] . "|" . $row['licenseID'];
    }

    return $licenseArray;
  }



  //search used for the organization autocomplete
  public function organizationAutocomplete($q) {
    $config = new Configuration;
    $organizationArray = array();

    //if the org module is installed get the org name from org database
    if ($config->settings->organizationsModule == 'Y') {
      $dbName = $config->settings->organizationsDatabaseName;

      $result = $this->db->query("SELECT CONCAT(A.name, ' (', O.name, ')') shortName, O.organizationID
                  FROM " . $dbName . ".Alias A, " . $dbName . ".Organization O
                  WHERE A.organizationID=O.organizationID
                  AND upper(A.name) like upper('%" . $q . "%')
                  UNION
                  SELECT name shortName, organizationID
                  FROM " . $dbName . ".Organization
                  WHERE upper(name) like upper('%" . $q . "%')
                  ORDER BY 1;");

    }else{

      $result = $this->db->query("SELECT organizationID, shortName
                  FROM Organization O
                  WHERE upper(O.shortName) like upper('%" . $q . "%')
                  ORDER BY shortName;");

    }


    while ($row = $result->fetch_assoc()) {
      $organizationArray[] = $row['shortName'] . "|" . $row['organizationID'];
    }



    return $organizationArray;
  }



  //search used for the license autocomplete
  /*
  public function licenseAutocomplete($q) {
    $config = new Configuration;
    $licenseArray = array();

    //if the org module is installed get the org name from org database
    if ($config->settings->licensingModule == 'Y') {
      $dbName = $config->settings->licensingDatabaseName;

      $result = $this->db->query("SELECT shortName, licenseID
                  FROM " . $dbName . ".License
                  WHERE upper(shortName) like upper('%" . $q . "%')
                  ORDER BY 1;");

    }

    while ($row = $result->fetch_assoc()) {
      $licenseArray[] = $row['shortName'] . "|" . $row['licenseID'];
    }



    return $licenseArray;
  }

*/

  //returns array of subject objects
  public function getGeneralDetailSubjectLinkID() {

    $query = "SELECT
          GDL.generalDetailSubjectLinkID
        FROM
          License R
          INNER JOIN LicenseSubject RSUB ON (R.licenseID = RSUB.licenseID)
          INNER JOIN GeneralDetailSubjectLink GDL ON (RSUB.generalDetailSubjectLinkID = GDL.generalDetailSubjectLinkID)
          LEFT OUTER JOIN GeneralSubject GS ON (GDL.generalSubjectID = GS.generalSubjectID)
          LEFT OUTER JOIN DetailedSubject DS ON (GDL.detailedSubjectID = DS.detailedSubjectID)
        WHERE
          R.licenseID = '" . $this->licenseID . "'
        ORDER BY
          GS.shortName,
          DS.shortName";


    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['generalDetailSubjectLinkID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $object = new GeneralDetailSubjectLink(new NamedArguments(array('primaryKey' => $row['generalDetailSubjectLinkID'])));
      array_push($objects, $object);
    }

    return $objects;
  }



  //returns array of subject objects
  public function getDetailedSubjects($licenseID, $generalSubjectID) {

    $query = "SELECT
        RSUB.licenseID,
        GDL.detailedSubjectID,
        DetailedSubject.shortName,
        GDL.generalSubjectID
      FROM
        LicenseSubject RSUB
        INNER JOIN GeneralDetailSubjectLink GDL ON (RSUB.GeneralDetailSubjectLinkID = GDL.GeneralDetailSubjectLinkID)
        INNER JOIN DetailedSubject ON (GDL.detailedSubjectID = DetailedSubject.detailedSubjectID)
      WHERE
        RSUB.licenseID = " . $licenseID . " AND GDL.generalSubjectID = " . $generalSubjectID . " ORDER BY DetailedSubject.shortName";

    //echo $query . "<br>";

    $result = $this->db->processQuery($query, 'assoc');

    $objects = array();

    //need to do this since it could be that there's only one request and this is how the dbservice returns result
    if (isset($result['detailedSubjectID'])) { $result = [$result]; }
    foreach ($result as $row) {
      $object = new DetailedSubject(new NamedArguments(array('primaryKey' => $row['detailedSubjectID'])));
      array_push($objects, $object);
    }

    return $objects;
  }



  //removes all license subjects
  public function removeAllSubjects() {

    $query = "DELETE
      FROM LicenseSubject
      WHERE licenseID = '" . $this->licenseID . "'";

    $result = $this->db->processQuery($query);
  }



  public function removeAllIsbnOrIssn() {
    $query = "DELETE
      FROM IsbnOrIssn
      WHERE licenseID = '" . $this->licenseID . "'";

    $result = $this->db->processQuery($query);
  }



  public function setIsbnOrIssn($isbnorissns) {
    $this->removeAllIsbnOrIssn();
    foreach ($isbnorissns as $isbnorissn) {
      if (trim($isbnorissn) != '') {
        $isbnOrIssn = new IsbnOrIssn();
        $isbnOrIssn->licenseID = $this->licenseID;
        $isbnOrIssn->isbnOrIssn = $isbnorissn;
        $isbnOrIssn->save();
      }
    }
  }
}
?>
