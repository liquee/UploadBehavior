<?php
/**
 * UploadBehavior.php
 * 
 * Yii framework extension
 * 
 * This class extends CActiveRecordBehavior.
 *
 * This extension takes uploaded file handling. It generates unique file name for uploaded file, and
 * saves it in a specified directory. It assigns the file name to the appropriate database table field.
 * It automatically removes file when you delete a database record. 
 * 
 * EXAMPLE:
 * In model class add the following:
 * 
 * public function behaviors(){
 *     return array(
 *         'UploadBehavior' => array(
 *         'class' => 'ext.UploadBehavior.UploadBehavior', // class location
 *         'uploadDir' => 'webroot.images', // upload dir (alias path)
 *         'attribute' => 'file', // model attribute name containing file name
 *     );
 * }
 * 
 * @author Tomasz Leśniak <t.lesniak@gmail.com>
 * @version 1.0
 * 
 */
class UploadBehavior extends CActiveRecordBehavior {

    public $uploadDir = 'webroot.uploads'; // default upload dir (alias path)
    public $attribute = 'file'; // model attribute name containing file name

    
    private $_uf = null;  // CUploadedFile
    private $_pf = '';    // previous file name
    
    /**
     * beforeValidate
     * 
     * Handles uploaded file. Saves previous file name (we will needed it in case of update, to delete previous file).
     * We don't do anything more befor validation
     * 
     * @param event
     */
    public function beforeValidate($event)
    {
        // if file was uploaded
        if($this->_uf = CUploadedFile::getInstance($this->owner, $this->attribute))
        {
            $this->_pf = $this->owner->{$this->attribute};
            $this->owner->{$this->attribute} = $this->_uf;
        }
    }
    
    /**
     * beforeSave
     * 
     * If uploaded file passed through the validation we can save it in the appropriate place on our hard drive and
     * in the database.
     * 
     * @param event 
     */
    public function beforeSave($event)
    {
        // if file was uploaded
        if($this->_uf){
            $fn = $this->generateFileName($this->_uf->getExtensionName()); // generate new file name
            $fp = $this->getFileFullPath($fn); // get full file path

            if ($this->_uf->saveAs($fp))
            {
                $this->owner->{$this->attribute} = $fn;

                // delete previous file if exists (if we are updating db record)
                if($this->_pf)
                    @unlink($this->getFileFullPath($this->_pf));
            }
            else {
                // if something went wrong
                $event->isValid = false;
                $this->owner->addError($this->attribute, 'We can\'t save the file. Try again later.');
            }
            
        }
    }
	
    /**
     * beforeDelete
     * 
     * Deletes file if exists when deleting database record
     * 
     * @param event
     */
    public function beforeDelete($event)
    {
        if($fn = $this->owner->{$this->attribute})
            @unlink($this->getFileFullPath($fn));
    }

    /**
     * getFileFullPath
     * 
     * Generates full file path
     * 
     * @param string $fname file name
     * @return string full file path
     */
    private function getFileFullPath($fname)
    {
        return Yii::getPathOfAlias($this->uploadDir).'/'. $fname;
    }

    /**
     * generateFileName
     * 
     * Generates unique file name
     * 
     * @param string $ext file extension WITHOUT the dot
     * @return string unique file name with extension
     */
    private function generateFileName($ext)
    {
        return uniqid().'.'. strtolower($ext);
    }
    
}// class UploadBehavior 

?>