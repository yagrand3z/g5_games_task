<?
/**
* G5Games Test Task Solution
*
* This application calculating 
* count of lukie tickets in range inputed by GET request
* inputted by GET request. Check \Config class and try increase or 
* decrease calculation count of chars in ticket number.
*
* @author YAGrand <yagrand@live.ru>
* @version 1.0
*/

(new App())->run();

/**
* App Class
* 
* Main applications class
*/
class App{
	private $start_range, $end_range, $config, $locale;
	
	/**
	* Preparing application resources
	*/
	function __construct(){
		$this->config = new \Config();
		$this->locale = new \Locale($this->config->locale_code, $this->config->base_locale_path);
	}
	
	/**
	* Start of application
	*/
	public function run(){
		$result = $this->handleInput();
		if($result->isSuccess()){
			$result = $this->calcLuckieNumbersCount();
		}
		$this->renderResult($result);
	}
	
	/**
	* Parsing and verificate input parameters
	*/
	private function handleInput():\Result{
		$result = new \Result();
		$get_start_range = preg_replace("/[^0-9]/", '', $_GET['start']);
		if(strlen($get_start_range)!=$this->config->input_number_chars_count){
			$result->addError(new \Error($this->locale->get('input_error_start_isnt_eq_required_chars_count', [$this->config->input_number_chars_count])));
		}
		$this->start_range = $get_start_range;
		
		if(intval($this->start_range)<1)
			$result->addError(new \Error($this->locale->get('input_error_start_cant_be_less_one', [str_pad('1', $this->config->input_number_chars_count, "0", STR_PAD_LEFT)])));
		
		$get_end_range = preg_replace("/[^0-9]/", '', $_GET['end']);
		if(strlen($get_end_range)!=$this->config->input_number_chars_count){
			$result->addError(new \Error($this->locale->get('input_error_end_isnt_eq_required_chars_count', [$this->config->input_number_chars_count])));
		}
		$this->end_range = $get_end_range;
		
		if(intval($this->start_range)>intval($this->end_range))
			$result->addError(new \Error($this->locale->get('input_error_start_greate_then_end')));
		
		$result->setSuccess(!$result->hasError());
		return $result;
	}
	
	/**
	* Main calculating function, fetching numbers 
	* and call check function, is it luckie or not.
	*/
	private function calcLuckieNumbersCount():\Result{
		$time_start = microtime(true);
		$number = intval($this->start_range);		
		$end = intval($this->end_range);
		$half_chars_count = $this->config->input_number_chars_count/2;
		
		//Economy a littel time
		if(strlen($number)<=$half_chars_count){
			if(strlen($end)<=$half_chars_count){
				$number = $end++;
			}else{
				$number = str_pad('1', $half_chars_count+1, '0');
			}
		}
		
		$counter = 0;
		for($number; $number<=$end; $number++){
			if($this->isLuckie($number))
				$counter++;
		}
		
		$data = [
			'counter' => $counter,
			'time' => microtime(true) - $time_start,
		];
		return new \Result(true, $data);
	}
	
	/**
	* Helper function, adding missing zeros to number, 
	* split it and call summing function.
	*/
	private function isLuckie(int $number){
		$s_number = str_pad($number, $this->config->input_number_chars_count, "0", STR_PAD_LEFT);
		$half_chars_count = $this->config->input_number_chars_count/2;
		$left = $this->sumChars(substr($s_number,0, $half_chars_count));
		$right = $this->sumChars(substr($s_number, -$half_chars_count));
		return $left==$right;
	}
	
	/**
	* Summing digits of number recursively, until one digit left
	*/
	private function sumChars(string $string){
		do{
			$string = array_sum(str_split($string));
		}while(strlen($string)>1);
		return $string;
	}
	
	/**
	* Rendering result of application work, 
	* it's may be just number, or text, or json if it is API
	*/
	private function renderResult(\Result $result){
		if($result->isSuccess()){
			echo $this->locale->get('result_success_message', [$this->start_range, $this->end_range, round($result->getData()['time'], 4), $result->getData()['counter'],]);
		}else{
			foreach($result->getErrors() as $error)
				echo $this->locale->get('input_error', [$error->getMessage()]).'</br>';
		}
	}
}

/**
* Config Class
* 
* Containing application configuration, and give access to it
*/
class Config {
	
	private $config = [
				'input_number_chars_count' => 6, //Must be even or will throw exception
				'locale_code' => 'ru_RU', //Interface strings in code is bad practice all times
				'base_locale_path' => '/locales'
			];
	
	function __construct(){
		$this->config['document_root'] = $_SERVER['DOCUMENT_ROOT'];
		$this->config['base_locale_path'] = $this->document_root.$this->base_locale_path;
		
		if($this->input_number_chars_count % 2)
			throw new \Exception('Parameter "input_number_chars_count" is not even');
	}
	
	public function __get($param_name){
		if(!isset($this->config[$param_name]))
			throw new \Exception('Parameter with name "'.$param_name.'" is not setted');
		
		return $this->config[$param_name];
	}
}

/**
* Result Class
* 
* This class using for return expanded result of function. 
*/
class Result {
	private $data, $errors, $is_success;
	function __construct(bool  $is_success = false, $data = [], array $errors = []){
		$this->is_success = $is_success;
		$this->errors = [];
		if(!empty($errors)){
			foreach($errors as $error_key => $error)
				$this->addError($error);
		}
		$this->data = $data;
	}
	
	public function hasError(){
		return !empty($this->errors);
	}
	
	public function isSuccess(){
		return $this->is_success;
	}
	
	public function getErrors(){
		return $this->errors;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function setSuccess(bool  $val){
		$this->is_success = $val;
	}
	
	public function addError($error){
		if(!($error instanceof \Error))
			throw new \Exception('Error is not instance of \Error');
		$this->errors[] = $error;
	}
	
	public function setData($val){
		$this->data = $val;
	}
}

/**
* Locale Class
* 
* Containing and handel application locale strings. 
*/
class Locale {
	private $strings = [];
	function __construct(string $locale_code, string $base_locale_path){
		if(empty($locale_code))
			throw new \Exception('$locale_code can not be empty');
		$base_locale_path .= '/'.$locale_code.'.json';
		if(!file_exists($base_locale_path))
			throw new \Exception('Locale file "'.$base_locale_path.'" is not exists');
		$parse_result = json_decode(file_get_contents($base_locale_path), true);
		if(!is_array($parse_result))
			throw new \Exception('Locale file parse error');
		$this->strings = $parse_result;
	}
	
	public function get(string $string_id, array $replace = []){
		if(!isset($this->strings[$string_id]))
			throw new \Exception('String "'.$string_id.'" is not setted');
		$args = array_merge([$this->strings[$string_id]],$replace);
		return call_user_func_array('sprintf', $args);
	}
}