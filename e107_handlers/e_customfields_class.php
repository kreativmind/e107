<?php


	/**
	 * e107 website system
	 *
	 * Copyright (C) 2008-2017 e107 Inc (e107.org)
	 * Released under the terms and conditions of the
	 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
	 *
	 */
	class e_customfields
	{
		private $_fieldTypes = array(
			'number', 'email', 'url', 'password', 'text', 'tags', 'textarea',
			'bbarea', 'image', 'file', 'icon', 'datestamp', 'checkboxes', 'dropdown', 'radio',
			'userclass', 'user', 'boolean', 'checkbox', 'hidden', 'lanlist', 'language', 'country', 'video'

		);

		private $_config = array();

		private $_data = array();

		private $_field_limit = 20;

		private $_tab = array('additional'=>'Additional FieldsXX');


		function __construct()
		{
			asort($this->_fieldTypes);

		}

		public function getFieldTypes()
		{

			return $this->_fieldTypes;
		}


		/**
		 * Load the configuration for all custom fields
		 * @param string $data - json custom-field configuration data.
		 * @return $this
		 */
		public function loadConfig($data)
		{
			if(is_array($data))
			{
				$this->_config = $data;
				return $this;
			}

			$this->_config = e107::unserialize($data);

			return $this;
		}


		/**
		 * Load a set of custom-field data for the current configuration.
		 * @param string $data json custom-field form data.
		 * @return $this
		 */
		public function loadData($data)
		{
			$this->_data = e107::unserialize($data);

			return $this;
		}


		public function getConfig()
		{
			return $this->_config;
		}


		public function getData()
		{
			return $this->_data;
		}


		public function setTab($key, $label)
		{
			$this->_tab = array((string) $key => (string) $label);

			return $this;
		}


		public function getFieldValue($key, $parm=array())
		{
			$tp = e107::getParser();

			$value = $this->_data[$key];
			$raw = (!empty($parm['mode']) && $parm['mode'] === 'raw') ? true : false;
			$type = (!empty($parm['type'])) ? $parm['type'] : null;

			$fieldType = $this->_config[$key]['type'];

			switch($fieldType)
			{
				case "dropdown":
				case "checkboxes":
				case "radio":
					return ($raw) ? $value : e107::getForm()->renderValue($key,$value,$this->_config[$key]);
				break;

				case "video":
					return ($raw) ? 'https://www.youtube.com/watch?v='.str_replace(".youtube", '', $value) : $tp->toVideo($value);
				break;

				case "image":
					return ($raw) ? $tp->thumbUrl($value) : $tp->toImage($value);
					break;

				case "icon":
					return ($raw) ? str_replace(".glyph", '', $value) : $tp->toIcon($value);
					break;

				case "country":
					return ($raw) ? $value : e107::getForm()->getCountry($value);
					break;

				case "tags":
					return ($raw) ? $value : $tp->toLabel($value,$type);
					break;

				case "lanlist":
				case "language":
					return ($raw) ? $value : e107::getLanguage()->convert($value);
					break;

				case "datestamp":
					return ($raw) ? $value : $tp->toDate($value);
					break;

				case "file":
					return ($raw) ? $tp->toFile($value, array('raw'=>1)) : $tp->toFile($value);
					break;

				case "url":
				case "email":
					return ($raw) ? $value : $tp->toHtml($value);
					break;

				case "user":
					return ($raw) ? $value : e107::getSystemUser($value,true)->getName();
					break;

				case "userclass":
					return ($raw) ? $value : e107::getUserClass()->getName($value);
					break;

				case "textarea":
				case "bbarea":
					return $tp->toHtml($value, true);
					break;


				default:
					return $tp->toHtml($value);
			}

		}


		public function renderTest()
		{

				$text = '<table class="table table-bordered table-striped">
			<tr><th>Name</th><th>Title<br /><small>&#123;CPAGEFIELDTITLE: name=x&#125;</small></th><th>Normal<br /><small>&#123;CPAGEFIELD: name=x&#125;</small></th><th>Raw<br /><small>&#123;CPAGEFIELD: name=x&mode=raw&#125;</small></th></tr>';

			foreach($this->_data as $ok=>$v)
			{

				$text .= "<tr><td>".$ok."</td><td>".$this->getFieldTitle($ok)."</td><td>".$this->getFieldValue($ok)."</td><td>".$this->getFieldValue($ok, array('mode'=>'raw'))."</td></tr>";
			}

			$text .= "</table>";

			return $text;



		}



		public function getFieldTitle($key)
		{

			if(!empty($this->_config[$key]['title']))
			{
				return $this->_config[$key]['title'];
			}

			return null;
		}




		public function renderConfigForm($name)
		{
			$frm = e107::getForm();
			$curVal = $this->_config;
			$value = array();

			if(!empty($curVal))
			{
				$i = 0;
				foreach($curVal as $k=>$v)
				{
					$v['key'] = $k;
					$value[$i] = $v;
					$i++;
				}
			}


			$text = "<table class='table table-striped table-bordered'>
			<colgroup>
				<col />
				<col />
				<col />
				<col style='width:40%' />
			</colgroup>
			<tbody>
				<tr><th>".LAN_NAME."</th><th>".LAN_TITLE."</th><th>".LAN_TYPE."</th><th>Params</th><th>".LAN_TOOLTIP."</th></tr>
				";

			for ($i = 0; $i <= $this->_field_limit; $i++)
			{

				$writeParms = array(
				//	'class' => 'form-control',
					'useValues' => 1,
					'default'   => 'blank',
					'data-src' => e_REQUEST_URI,

				);

				$parmsWriteParms= array(
					'size' => 'block-level',
					'placeholder' => $this->getCustomFieldPlaceholder($value[$i]['type'])

				);

				$fieldName = $frm->text($name.'['.$i.'][key]', $value[$i]['key'],30, array('pattern'=>'^[a-z0-9-]*'));
				$fieldTitle = $frm->text($name.'['.$i.'][title]',$value[$i]['title'], 80);
				$fieldType = $frm->select($name.'['.$i.'][type]',$this->getFieldTypes(),$value[$i]['type'], $writeParms);
				$fieldParms = $frm->text($name.'['.$i.'][writeParms]',$value[$i]['writeParms'], 255, $parmsWriteParms);
				$fieldHelp = $frm->text($name.'['.$i.'][help]',$value[$i]['help'], 255, array('size'=>'block-level'));
			   $text .= "<tr><td>".$fieldName."</td><td>".$fieldTitle."</td><td>".$fieldType."</td><td>".$fieldParms."</td><td>".$fieldHelp."</td></tr>";
			}

			$text .= "</tbody></table>";


			return $text;

		}


		/**
		 * @param $type
		 * @return null|string
		 */
		private function getCustomFieldPlaceholder($type)
		{
			switch($type)
			{
				case "radio":
				case "dropdown":
				case "checkboxes":
					return 'eg. { "optArray": { "blue": "Blue", "green": "Green", "red": "Red" }, "default": "blank" }';
					break;

				case "datestamp":
					return 'eg. (Optional) { "format": "yyyy-mm-dd" }';
				break;


				default:

			}


			return null;


		}


		/**
		 *
		 * @param $fieldName
		 * @param e_admin_ui $ui
		 * @return $this
		 */
		public function setAdminUIConfig($fieldName, e_admin_ui &$ui)
		{
			$fields = array();

			$tabKey = key($this->_tab);
			$ui->addTab($tabKey, $this->_tab[$tabKey]);


			foreach($this->_config as $k=>$fld)
			{
				$fld['tab'] = $tabKey;
				$fld['data'] = false;
				$key = $fld['key'];

				if($fld['type'] === 'icon')
				{
					$fld['writeParms'] .= "&glyphs=1";
				}

				if($fld['type'] == 'checkboxes')
				{
					if($tmp = e107::getParser()->isJSON($fld['writeParms']))
					{
						$fld['writeParms'] = $tmp;
					}

					$fld['writeParms']['useKeyValues'] = 1;
				}

				$fields[$fieldName.'__'.$key] = $fld;

			}

			$ui->setFieldAttr($fields);

			return $this;


		}


		/**
		 * @param $fieldname
		 * @param e_admin_ui $ui
		 * @return $this
		 */
		public function setAdminUIData($fieldname, e_admin_ui &$ui)
		{

			$ui->getModel()->set($fieldname, null);

			foreach($this->_data as $key=>$value)
			{
				$ui->getModel()->set($fieldname.'__'.$key, $value);
			//	e107::getDebug()->log($fieldname.'__'.$key.": ".$value);
			}

			return $this;


		}


		/**
		 * Process Posted form data and compiled Configuration Form data if found.
		 * @param string $fieldname
		 * @param array $postData all posted data.
		 * @return array
		 */
		public function processConfigPost($fieldname, $postData)
		{

			if(empty($postData[$fieldname]))
			{
				return $postData;
			}

			$newdata = $postData[$fieldname];

			$new = array();
			foreach($newdata as $fields)
			{
				if(empty($fields['key']) || empty($fields['type']))
				{
					continue;
				}


				$key = $fields['key'];
				unset($fields['key']);
				$new[$key] = $fields;


			}

			return $new;

		}


		/**
		 * Process all posted data and compile into a single field array.
		 * @param $fieldname
		 * @param $new_data
		 * @return null
		 * @internal param array $newdata - all posted data.
		 */
		public function processDataPost($fieldname, $new_data)
		{
			if(empty($new_data))
			{
				return null;
			}

			unset($new_data[$fieldname]); // Reset.

			$len = strlen($fieldname);

			foreach($new_data as $k=>$v)
			{
				if(substr($k,0,$len) === $fieldname)
				{
					list($tmp,$newkey) = explode("__",$k);
					$new_data[$fieldname][$newkey] = $v;
					unset($new_data[$k]);


				}

			}

			return $new_data;

		}



	}