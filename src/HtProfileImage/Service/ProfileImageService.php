<?php
namespace HtProfileImage\Service;

use ZfcUser\Entity\UserInterface;
use HtProfileImage\Form\ProfileImageForm;
use HtProfileImage\Form\ProfileImageInputFilter;
use HtProfileImage\Form\ProfileImageValidator;
use ZfcBase\EventManager\EventProvider;

class ProfileImageService extends EventProvider implements ProfileImageServiceInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $options;

    protected $storageModel;

    protected $filterManager;

    protected $imagine;

    public function uploadImage(UserInterface $user, array $files)
    {
        $form = $this->getServiceLocator()->get('HtProfileImage\ProfileImageForm');
        $this->getEventManager()->trigger(__METHOD__, $this, array(
            'files' => $files,
            'form' => $form,
            'user' => $user
        ));
        $validator = new ProfileImageValidator();
        $form->setData($files);
        $form->setInputFilter($validator);
        if ($form->isValid()) { // check if image is valid
            $inputFilter = new ProfileImageInputFilter($this->getOptions()->getUploadDirectory(), $user);
            $inputFilter->init();
            $form->setInputFilter($inputFilter);    
            $result = $form->isValid();// upload the image  
            $file = $inputFilter->getUploadTarget();
            $newFileName = $this->getStorageModel()->getUserImage($user->getId());
            $filterAlias = $this->getOptions()->getStorageFilter();
            if (!$filterAlias) {
                rename($file, $newFileName); //no filter alias given, just rename
            } else {
                $filter = $this->getFilterManager()->getFilter($filterAlias); 
                $image = $this->getImagine()->open($file);
                try {
                    $image = $filter->apply($image); // resize the image
                } catch (\Exception $e) {
                    return false;
                }
                $image->save($newFileName); // store the image
            }
            $this->getEventManager()->trigger(__METHOD__ . '.post', $this, array(
                'file_name' => $newFileName,
                'user' => $user
            ));

            return true;
        } 

        return false;               
    }

    public function getUserImage(UserInterface $user)
    {
        $fileName = $this->getStorageModel()->getUserImage($user->getId());
        if ($this->getStorageModel()->getUserImage($user)) {
            $image = $this->getImagine()->open($file);
        } else {
            
        }
        $image = $this->getImagine()->open($file);
    }

    public function getOptions()
    {
        if (!$this->options) {
            $this->options = $this->getServiceLocator()->get('HtProfileImage\ModuleOptions');
        }

        return $this->options;
    }

    public function getStorageModel()
    {
        if (!$this->storageModel) {
            $this->storageModel = $this->getServiceLocator()->get('HtProfileImage\StorageModel');
        }
        
        return $this->storageModel;
    }

    public function getFilterManager()
    {
        if (!$this->filterManager) {
            $this->filterManager = $this->getServiceLocator()->get('HtImgModule\Imagine\Filter\FilterManager');
        }

        return $this->filterManager;
    }

    public function getImagine()
    {
        if (!$this->imagine) {
            $this->imagine = $this->getServiceLocator()->get('HtImg\Imagine');
        }

        return $this->imagine;
    }
}
