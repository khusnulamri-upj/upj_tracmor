<?php
	/**
	 * This class creates a panel that lists the attachments for any particular record.
	 * It will allow the user to download or delete any of the attachments.
	 *
	 */
	class QAttachments extends QPanel {
		
		protected $intAttachmentCount;
		protected $objAttachmentArray;
		protected $lblAttachments;
		protected $pnlAttachments;
		protected $arrAttachments;

		public function __construct($objParentObject, $strControlId = null, $intEntityQtypeId = null, $intEntityId = null) {
			parent::__construct($objParentObject, $strControlId);
			
			$this->intAttachmentCount = Attachment::CountByEntityQtypeIdEntityId($intEntityQtypeId, $intEntityId);
			if ($this->intAttachmentCount > 0) {
				$this->strTemplate = __DOCROOT__ .  __SUBDIRECTORY__ . '/common/QAttachments.tpl.php';
				
				$this->lblAttachments_Create();
				
				$this->pnlAttachments = new QPanel($this);
				$this->pnlAttachments->strTemplate = __DOCROOT__  . __SUBDIRECTORY__ . '/common/attachments.tpl.php';
				$this->pnlAttachments->Display = false;
				$this->objAttachmentArray = Attachment::LoadArrayByEntityQtypeIdEntityId($intEntityQtypeId, $intEntityId);
				$this->arrAttachments = array();
				foreach ($this->objAttachmentArray as $key => $objAttachment) {
					
					$strAttachment = sprintf('<strong><a href="' . __PHP_ASSETS__ . '/download.php?tmp_filename=%s&attachment_id=%s" target="_blank" style="color:blue;">%s</a></strong> (%s bytes) %s by %s  ', $objAttachment->TmpFilename, $objAttachment->AttachmentId, $objAttachment->Filename, $objAttachment->Size, $objAttachment->CreationDate, $objAttachment->CreatedByObject->__toStringFullName());
					
					$lblDelete = new QLabel($this->pnlAttachments);
					$lblDelete->Text = 'Delete<br/>';
					$lblDelete->ForeColor = '#555555';
					$lblDelete->FontUnderline = true;
					$lblDelete->SetCustomStyle('cursor', 'pointer');
					$lblDelete->HtmlEntities = false;
					$lblDelete->ActionParameter = $objAttachment->AttachmentId;
					$lblDelete->AddAction(new QClickEvent(), new QConfirmAction('Are you sure you want to delete this attachment?'));
					$lblDelete->AddAction(new QClickEvent(), new QServerControlAction($this, 'lblDelete_Click'));
					QApplication::AuthorizeControl($objAttachment, $lblDelete, 3);
					
					$this->arrAttachments[$key]['strAttachment'] = $strAttachment;
					$this->arrAttachments[$key]['lblDelete'] = $lblDelete;
				}
			}
			else {
				$this->Display = false;
			}
		}
		
		protected function lblAttachments_Create() {
			$this->lblAttachments = new QLabel($this);
			$this->lblAttachments->HtmlEntities=false;
			if ($this->intAttachmentCount == 1) {
				$this->lblAttachments->Text = sprintf('<img src="../images/icons/attachment.gif" style="vertical-align:bottom;"><span style="text-decoration:underline;">%s Attachment</span>', $this->intAttachmentCount);
			}
			else {
				$this->lblAttachments->Text = sprintf('<img src="../images/icons/attachment.gif" style="vertical-align:bottom;><span style="text-decoration:underline;">%s Attachments</span>', $this->intAttachmentCount);
			}
			$this->lblAttachments->ForeColor = '#555555';
			//$this->lblAttachments->FontUnderline = true;
			//$this->lblAttachments->FontBold = true;
			$this->lblAttachments->SetCustomStyle('cursor', 'pointer');
			$this->lblAttachments->SetCustomStyle('padding','3px');
			$this->lblAttachments->AddAction(new QClickEvent(), new QAjaxControlAction($this, 'lblAttachments_Click'));
		}
		
		public function lblAttachments_Click($strFormId, $strControlId, $strParameter) {
			if ($this->pnlAttachments->Display) {
				$this->pnlAttachments->Display = false;
				if ($this->intAttachmentCount == 1) {
					$this->lblAttachments->Text = sprintf('<img src="../images/icons/attachment.gif" style="vertical-align:bottom;"><span style="text-decoration:underline;">%s Attachment</span>', $this->intAttachmentCount);
				} else {
					$this->lblAttachments->Text = sprintf('<img src="../images/icons/attachment.gif" style="vertical-align:bottom;"><span style="text-decoration:underline;">%s Attachments</span>', $this->intAttachmentCount);
				}
			}
			else {
				$this->pnlAttachments->Display = true;
				$this->lblAttachments->Text = '<img src="../images/icons/attachment.gif" style="vertical-align:bottom;"><span style="text-decoration:underline;">Hide Attachments</span>';
			}
		}
		
		public function lblDelete_Click($strFormId, $strControlId, $strParameter) {
			$objAttachment = Attachment::Load($strParameter);
			if (AWS_S3) {
				require(__DOCROOT__ . __PHP_ASSETS__ . '/S3.php');
				$objS3 = new S3(AWS_ACCESS_KEY, AWS_SECRET_KEY);
				$strS3Path = (AWS_PATH != '') ? ltrim(AWS_PATH, '/') . '/' : '';
				$objS3->deleteObject(AWS_BUCKET, $strS3Path . 'attachments/' . $objAttachment->TmpFilename);
			}
			else {
				if (file_exists($objAttachment->Path)) {
					unlink($objAttachment->Path);
				}
			}
			$objAttachment->Delete();
			if ($this->objParentControl) {
				$this->objParentControl->pnlAttachments_Create();
			}
			else {
				$this->objForm->pnlAttachments_Create();
			}
		}

		public function GetControlHtml() {
/*			if ($this->objFileAsset) {
				$this->strCssClass = 'FileAssetPanelItem';
				$this->SetCustomStyle('background', 'url(' . $this->objFileAsset->ThumbnailUrl() . ') no-repeat');
			} else {
				$this->strCssClass = 'FileAssetPanelItemNone';
				$this->SetCustomStyle('background', null);
			}*/
			return parent::GetControlHtml();
		}

		public function __get($strName) {
			switch ($strName) {
				case 'objAttachmentArray': return $this->objAttachmentArray;
				case 'lblAttachments': return $this->lblAttachments;
				case 'pnlAttachments': return $this->pnlAttachments;
				case 'arrAttachments': return $this->arrAttachments;

				default:
					try {
						return parent::__get($strName);
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

		public function __set($strName, $mixValue) {
			$this->blnModified = true;

			switch ($strName) {
				/*case 'EntityQtypeId':
					try {
						return ($this->intEntityQtypeId = $mixValue);
					} catch (QCallerException $objExc) {						
						$objExc->IncrementOffset();
						throw $objExc;
					}*/
					
					default:
					try {
						return parent::__set($strName, $mixValue);
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}
	}
?>