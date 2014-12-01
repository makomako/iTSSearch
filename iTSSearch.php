<?php

class iTSSearchApiParam {
	protected $_ParameterKey;
//	protected $_Description;
	protected $_Required;
	protected $_Values;
//	protected $_ValuesDescription;
	
//	public function __construct($parameterKey, $description, $required, $values, $valueDescription) {
	public function __construct($parameterKey, $required, $values) {
		$this->_ParameterKey = $parameterKey;
//		$this->_Description =  $description;
		$this->_Required = $required;
		$this->_Values = $values;
//		$this->_ValuesDescription = $valueDescription;
		return true;
	}
	
	public function setValues($values) {
		$this->_Values = $values;
		return true;
	}
	
	public function getParameterKey() {
		return $this->_ParameterKey;
	}
	
	public function isRequired() {
		return $this->_Required;
	}
	
	public function getValues() {
		return $this->_Values;
	}
}

class iTSSearchApi {
	protected $_requestURL;
	protected $_params;
	
	public function __construct() {
		$this->_requestURL = 'https://itunes.apple.com/search';
		$this->_params[] = new iTSSearchApiParam('term', true, null);
		$this->_params[] = new iTSSearchApiParam('country', true, 'jp');
//		$this->_params[] = new iTSSearchApiParam('media', false, 'all');
		$this->_params[] = new iTSSearchApiParam('media', false, 'ebook');
		$this->_params[] = new iTSSearchApiParam('entity', false, null);
		$this->_params[] = new iTSSearchApiParam('attribute', false, null);
		$this->_params[] = new iTSSearchApiParam('callback', false, null);
		$this->_params[] = new iTSSearchApiParam('limit', false, 50);
		$this->_params[] = new iTSSearchApiParam('lang', false, 'ja_jp');
		$this->_params[] = new iTSSearchApiParam('version', false, 2);
		$this->_params[] = new iTSSearchApiParam('explicit', false, 'Yes');
		
	}
	
	public function setTerm($term) {
		foreach ($this->_params as $param) {
			if ($param->getParameterKey() === 'term') {
				$param->setValues($term);
				return true;
			}
		}
		return false;
	}
	
	public function getRequestURL() {
		/** @var iTSSearchApiParam $param */
		$params = '';
		foreach ($this->_params as $param) {
			if (!$param->isRequired() && is_null($param->getValues())) {
				continue;
			}
			$params .= $param->getParameterKey() . '=' . urlencode($param->getValues()) . '&';
		}
		return $this->_requestURL . '?' . $params;
	}
}

$iTSSearchApi = new iTSSearchApi();

$term = '';
if (array_key_exists('submit', $_POST)) {
	$term = $_POST['term'];
	$encodedTerm = urlencode(trim($term));
	$url = "https://itunes.apple.com/search?term={$encodedTerm}&country=jp&lang=ja_jp&limit=200&media=ebook&entity=ebook";
// 	$url = "https://itunes.apple.com/search?term={$encodedTerm}&country=jp&media=all&limit=200";
	
	$iTSSearchApi->setTerm($_POST['term']);
	$url = $iTSSearchApi->getRequestURL();
	
	// 履歴保存
	$fpw = fopen('./history.log', 'a');
	fwrite($fpw, "{$term}\n");
	fclose($fpw);
	
	// 問い合わせ
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$receiveData = curl_exec($curl);
	curl_close($curl);
	
	$data = json_decode($receiveData);
	// 取得したデータをタイトル順に並べ替え
	
}

// 履歴取得
$fpv = fopen('./history.log', 'r');
$history = array();
while ($row = fgetcsv($fpv)) {
	$history[] = $row;
}
fclose($fpv);
$history = array_reverse($history);
for ($i = 0; $i < 8; $i++) {
	echo '<a href="javascript:;" onClick="form.term.value=\'' . "{$history[$i][0]}" . '\';">' . "{$history[$i][0]}" . '</a>&nbsp;|&nbsp;';
	//echo "<a href=\"javascript: submitHistory(this);\">" . "{$history[$i][0]}" . "</a> | ";
}
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<script type="text/javascript">
			function submitHistory(anchor) {
				form.term.value = anchor.innerText;
				form.submit.submit();
			}
		</script>
		<style type="text/css">
			td {
				border:solid 1px;
			}
		</style>
	</head>
	<body>
		<form id="form" action="" method="post">
			<input id="term" name="term" type="text" value="<?php echo $term; ?>"/>
			<input id="submit" name="submit" type="submit" value="iBooks Store検索"/>
		</form>
		<?php if (isset($data)): ?>
			<?php if (0 < $data->resultCount): ?>
				「<?php echo $term; ?>」のiBooks Store検索結果(<?php echo $data->resultCount; ?>件)</br>
				<table style="border: solid 1px;">
					<thead>
						<tr>
							<td>アートワーク</td>
							<td>タイトル</td>
							<td>作者</td>
							<td>概要</td>
							<td>価格</td>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($data->results as $result ): ?>
							<tr>
								<td><img src="<?php echo $result->artworkUrl100; ?>" /></td>
								<td><a href="<?php echo $result->trackViewUrl; ?>" target="_blank"><?php echo $result->trackName; ?></a></td>
								<td><?php echo $result->artistName; ?></br>(<?php echo $result->artistId; ?>)</td>
								<td><?php echo $result->description; ?></td>
								<td><?php echo $result->formattedPrice ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				該当なし
			<?php endif; ?>
		<?php endif; ?>
		<pre><?php var_dump($data); ?></pre>
	</body>
</html>