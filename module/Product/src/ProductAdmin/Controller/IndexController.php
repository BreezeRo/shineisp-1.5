<?php
/**
* Copyright (c) 2014 Shine Software.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions
* are met:
*
* * Redistributions of source code must retain the above copyright
* notice, this list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright
* notice, this list of conditions and the following disclaimer in
* the documentation and/or other materials provided with the
* distribution.
*
* * Neither the names of the copyright holders nor the names of the
* contributors may be used to endorse or promote products derived
* from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
* FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
* COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
* LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
* ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* @package Product
* @subpackage Controller
* @author Michelangelo Turillo <mturillo@shinesoftware.com>
* @copyright 2014 Michelangelo Turillo.
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @link http://shinesoftware.com
* @version @@PACKAGE_VERSION@@
*/

namespace ProductAdmin\Controller;

use Zend\InputFilter\InputFilter;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
	protected $recordService;
	protected $attributeService;
	protected $attributeSetService;
	protected $datagrid;
	protected $form;
	protected $filter;
	protected $newform;
	protected $newfilter;
	protected $settings;
	
	/**
	 * Class Constructor
	 * 
	 * @param \Product\Service\ProductServiceInterface $recordService
	 * @param \Product\Service\ProductAttributeServiceInterface $attributeService
	 * @param \Product\Service\ProductAttributeSetServiceInterface $attributeSetService
	 * @param \ProductAdmin\Form\ProductNewForm $newform
	 * @param \ProductAdmin\Form\ProductNewFilter $newformfilter
	 * @param \ProductAdmin\Form\ProductForm $form
	 * @param \ProductAdmin\Form\ProductFilter $formfilter
	 * @param \ZfcDatagrid\Datagrid $datagrid
	 * @param \Base\Service\SettingsServiceInterface $settings
	 */
	public function __construct(\Product\Service\ProductServiceInterface $recordService,
	                            \Product\Service\ProductAttributeServiceInterface $attributeService,
	                            \Product\Service\ProductAttributeSetServiceInterface $attributeSetService,
								\ProductAdmin\Form\ProductNewForm $newform, 
								\ProductAdmin\Form\ProductNewFilter $newformfilter, 
								\ProductAdmin\Form\ProductForm $form, 
								\ProductAdmin\Form\ProductFilter $formfilter, 
								\ZfcDatagrid\Datagrid $datagrid, 
								\Base\Service\SettingsServiceInterface $settings)
	{
		$this->productService = $recordService;
		$this->attributeService = $attributeService;
		$this->attributeSetService = $attributeSetService;
		$this->datagrid = $datagrid;
		$this->newform = $newform;
		$this->newfilter = $newformfilter;
		$this->form = $form;
		$this->filter = $formfilter;
		$this->settings = $settings;
	}
	
	
	/**
	 * List of all records
	 */
	public function indexAction ()
	{
		// prepare the datagrid
		$this->datagrid->render();
		
		// get the datagrid ready to be shown in the template view
		$response = $this->datagrid->getResponse();
		
		if ($this->datagrid->isHtmlInitReponse()) {
			$view = new ViewModel();
			$view->addChild($response, 'grid');
			return $view;
		} else {
			return $response;
		}
	}
	
    /**
     * Add new information
     */
    public function newAction ()
    {
    	 
    	$form = $this->newform;
    
    	$viewModel = new ViewModel(array (
    			'form' => $form,
    	));
    
    	$viewModel->setTemplate('product-admin/index/new');
    	return $viewModel;
    }
    
    /**
     * Add new information
     */
    public function setAction ()
    {
    	 
    	$form = $this->form;
    
    	$viewModel = new ViewModel(array (
    			'form' => $form,
    	));
    
    	$viewModel->setTemplate('product-admin/index/edit');
    	return $viewModel;
    }
    
    /**
     * Edit the main product information
     */
    public function editAction ()
    {
    	$id = $this->params()->fromRoute('id');
    	$form = $this->form;
    	$attributes = array();
    	
    	// Get the record by its id
    	$product = $this->productService->find($id);
    	
    	if(!empty($product) && $product->getId()){
    	    $attributeSetId = $product->getAttributeSetId();
    	    
    	    // Get the attributes and build the form on the fly
    	    $attributesIdx = $this->attributeSetService->findByAttributeSet($attributeSetId);
    	    foreach ($attributesIdx as $attributeId){
    	        $attributes[] = $this->attributeService->find($attributeId);
    	    }
    	    $form = $this->form->createAttributesElements($attributes);
    	}else{
    		$this->flashMessenger()->setNamespace('danger')->addMessage('The record has been not found!');
    		return $this->redirect()->toRoute('zfcadmin/product/default');
    	}
    	
    	// Bind the data in the form
    	if (! empty($product)) {
    		$form->bind($product);
    	}
    
    	$viewModel = new ViewModel(array (
    			'form' => $form,
    			'attributes' => $attributes,
    	));
    
    	return $viewModel;
    }
    
    /**
     * Prepare the data and then save them
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function processAction ()
    {
    	
    	if (! $this->request->isPost()) {
    		return $this->redirect()->toRoute(NULL, array (
    				'controller' => 'product',
    				'action' => 'index'
    		));
    	}
    	
    	$request = $this->getRequest();
    	$post = $this->request->getPost();
    	$form = $this->form;
    	
    	$attributeSetId = $post['attribute_set_id'];
    		
    	// Get the attributes and build the form on the fly
    	$attributesIdx = $this->attributeSetService->findByAttributeSet($attributeSetId);
    	foreach ($attributesIdx as $attributeId){
    		$attributes[] = $this->attributeService->find($attributeId);
    	}
    	$form = $this->form->createAttributesElements($attributes);
    	
    	$form->setData($post);

    	$filter = $form->getInputFilter();
    	
    	// set the input filter
    	$form->setInputFilter($filter);
    	
    	if (!$form->isValid()) {
    		$viewModel = new ViewModel(array (
    				'error' => true,
    				'form' => $form,
    				'attributes' => $attributes,
    		));
    		
    		$viewModel->setTemplate('product-admin/index/edit');
    		return $viewModel;
    	}
    
    	// Get the posted vars
    	$data = $form->getData();
    	var_dump($data);
    	die;
    	// Save the data in the database
    	$record = $this->productService->save($data);
    	$this->flashMessenger()->setNamespace('success')->addMessage('The information have been saved.');
    
    	return $this->redirect()->toRoute('zfcadmin/product/default', array ('action' => 'edit', 'id' => $record->getId()));
    }
    
    /**
     * Delete the records 
     *
     * @return \Zend\Http\Response
     */
    public function deleteAction ()
    {
    	$id = $this->params()->fromRoute('id');
    
    	if (is_numeric($id)) {
    
    		// Delete the record informaiton
    		$this->productService->delete($id);
    
    		// Go back showing a message
    		$this->flashMessenger()->setNamespace('success')->addMessage('The record has been deleted!');
    		return $this->redirect()->toRoute('zfcadmin/product/default');
    	}
    
    	$this->flashMessenger()->setNamespace('danger')->addMessage('The record has been not deleted!');
    	return $this->redirect()->toRoute('zfcadmin/product/default');
    }
    
}
