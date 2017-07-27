<?php
	//number of attachments, used to display on the tab so user knows whether to look on tab
		$resourceAcquisitionID = $_GET['resourceAcquisitionID'];
		$resourceAcquisition = new ResourceAcquisition(new NamedArguments(array('primaryKey' => $resourceAcquisitionID)));

		echo count($resourceAcquisition->getAttachments());
?>

