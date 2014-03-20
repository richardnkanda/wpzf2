<?php
namespace Album\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Client;
use Zend\Http\Client\Adapter\Curl;
use Zend\Json\Json;
use Album\Form\AlbumForm;
use Album\Model\Album;

class ClientController extends AbstractActionController
{
	public function indexAction()
	{
		$response = $this->getRestResponse("http://wpzf2.local/album-rest");
		$body = $response->getBody();
		$this->getResponse()->setContent($body);
		$albums = Json::decode($this->getResponse()->getContent(), Json::TYPE_OBJECT);
	
		$model = new ViewModel(
				array("albums" => $albums->albums));
		return $model;
	}
	
	public function addAction() {
		$form = new AlbumForm('album-client');
		$form->get('submit')->setValue('Add');
	
		$request = $this->getRequest();
		if ($request->isPost()) {
			$album = new Album();
			$form->setInputFilter($album->getInputFilter());
			$form->setData($request->getPost());
	
			if ($form->isValid()) {
				$data = $form->getData();
				$resp = $this->getRestResponse("http://wpzf2.local/album-rest", "POST", $data);
	
				// Redirect to list of albums
				return $this->redirect()->toRoute('albumclient');
			}
		}
	
		$model = new ViewModel(array('form' => $form));
		//         $model->setTemplate("album/album/add.phtml");
		return $model;
	}
	
	public function deleteAction() {
		$id = (int) $this->params()->fromRoute('id', 0);
		if (!$id) {
			return $this->redirect()->toRoute('albumclient');
		}
	
		$request = $this->getRequest();
		if ($request->isPost()) {
			$del = $request->getPost('del', 'No');
	
			if ($del == 'Yes') {
				$id = (int) $request->getPost('id');
				$resp = $this->getRestResponse(sprintf("http://wpzf2.local/album-rest/%s", $id), "DELETE");
			}
	
			// Redirect to list of albums
			return $this->redirect()->toRoute('albumclient');
		}
	
		$resp = $this->getRestResponse(sprintf("http://wpzf2.local/album-rest/%s", $id));
		$respData = Json::decode($resp->getBody());
		$album = new Album();
		$album->exchangeArray(get_object_vars($respData->album));
		$model = new ViewModel(array(
				'id'    => $id,
				'album' => $album
		));
		//         $model->setTemplate("album/album/delete.phtml");
		return $model;
	}
	
	public function editAction() {
		$id = (int) $this->params()->fromRoute('id', 0);
		if (!$id) {
			return $this->redirect()->toRoute('albumclient', array(
					'action' => 'add'
			));
		}
		$resp = $this->getRestResponse(sprintf("http://wpzf2.local/album-rest/%s", $id));
		$respData = Json::decode($resp->getBody());
		$album = new Album();
		$album->exchangeArray(get_object_vars($respData->album));
	
		$form  = new AlbumForm();
		$form->bind($album);
		$form->get('submit')->setAttribute('value', 'Edit');
	
		$request = $this->getRequest();
		if ($request->isPost()) {
			$form->setInputFilter($album->getInputFilter());
			$form->setData($request->getPost());
	
			if ($form->isValid()) {
				$resp = $this->getRestResponse(sprintf("http://wpzf2.local/album-rest/%s", $id), "POST", get_object_vars($form->getData()));
	
				// Redirect to list of albums
				return $this->redirect()->toRoute('albumclient');
			}
		}
	
		$model = new ViewModel(array(
				'id' => $id,
				'form' => $form,
		));
		//         $model->setTemplate("album/album/edit.phtml");
		return $model;
	}
	
	public function getRestResponse($uri, $method = "GET", $params = array()) 
	{
		$client = new Client();
		$client->setAdapter(new Curl());
		$client->setUri($uri);
		$client->setMethod($method);
		if ($method == "GET")
			$client->setParameterGet($params);
		else
			$client->setParameterPost($params);
		$response = $client->send();
		return $response;
	}
	
    
}
