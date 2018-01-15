<?php

	namespace Hcode\Model;

	use \Hcode\Model;
	use \Hcode\DB\Sql;
	use \Hcode\Mailer;

	class User extends Model {

		const SESSION = "User";
		const SECRET = "HcodePHP7_secret";

		protected $fields = [
			"iduser", "idperson", "deslogin", "despassword", "inadmin", "dtergister", "desperson", "desemail", "nrphone"
		];

		public static function login($login, $password):User
		{

			$db = new Sql();

			$results = $db->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
				":LOGIN"=>$login
			));

			if (count($results) === 0) {
				throw new \Exception("Não foi possível fazer login.");
			}

			$data = $results[0];

			if (password_verify($password, $data["despassword"])) {

				$user = new User();
				$user->setData($data);

				$_SESSION[User::SESSION] = $user->getValues();

				return $user;

			} else {

				throw new \Exception("Não foi possível fazer login.");

			}

		}

		public static function logout()
		{

			$_SESSION[User::SESSION] = NULL;

		}

		public static function verifyLogin($inadmin = true)
		{

			if (
				!isset($_SESSION[User::SESSION])
				|| 
				!$_SESSION[User::SESSION]
				||
				!(int)$_SESSION[User::SESSION]["iduser"] > 0
				||
				(bool)$_SESSION[User::SESSION]["iduser"] !== $inadmin
			) {
				
				header("Location: /admin/login");
				exit;

			}

		}

		public static function listAll()
		{
			$sql = new Sql();
			return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
		}

		public function save()
		{
			$sql = new Sql();
			$result = $sql->select("CALL sp_users_save(:DESPERSON, :DESLOGIN, :DESPASSWORD, :DESEMAIL, :NRPHONE, :INADMIN)", array(
				":DESPERSON"=>$this->getdesperson(),
				":DESLOGIN"=>$this->getdeslogin(),
				":DESPASSWORD"=>$this->getdespassword(),
				":DESEMAIL"=>$this->getdesemail(),
				":NRPHONE"=>$this->getnrphone(),
				":INADMIN"=>$this->getinadmin()
			));

			$this->setData($result[0]);
		}

		public function get($iduser)
		{
			$sql = new Sql();
			$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :IDUSER", array(
				":IDUSER"=>$iduser
			));
			$this->setData($results[0]);
		}

		public function update()
		{
			$sql = new Sql();
			$result = $sql->select("CALL sp_usersupdate_save(:IDUSER, :DESPERSON, :DESLOGIN, :DESPASSWORD, :DESEMAIL, :NRPHONE, :INADMIN)", array(
				":IDUSER"=>$this->getiduser(),
				":DESPERSON"=>$this->getdesperson(),
				":DESLOGIN"=>$this->getdeslogin(),
				":DESPASSWORD"=>$this->getdespassword(),
				":DESEMAIL"=>$this->getdesemail(),
				":NRPHONE"=>$this->getnrphone(),
				":INADMIN"=>$this->getinadmin()
			));

			$this->setData($result[0]);
		}

		public function delete()
		{
			$sql = new Sql();
			$sql->query("CALL sp_users_delete(:IDUSER)", array(
				":IDUSER"=>$this->getiduser()
			));
		}

		public static function getForgot($email)
		{
			$sql = new Sql();
			$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :EMAIL", array(
				":EMAIL"=>$email
			));

			if (count($results) === 0) {
				throw new \Exception("Não encontrado", 404);
				
			} else {
				$data = $results[0];
				$result = $sql->select("CALL sp_userspasswordsrecoveries_create(:IDUSER, :DESIP)", array(
					":IDUSER"=>$data['iduser'],
					":DESIP"=>$_SERVER["REMOTE_ADDR"]//pega o ip do usuario
				));

				if (count($result) === 0) {
					throw new \Exception("Não encontrado", 404);
					
				} else {
					$dataRecover = $result[0];
					//criptografia
					$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecover['idrecovery'], MCRYPT_MODE_ECB));
					$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
					$mailer = new Mailer($data["desemail"], $data["desperson"], "Trocar Senha da Hcode Store", "forgot", array(
						"name"=>$data["desperson"],
						"link"=>$link
					));

					$mailer->send();

					return $data;
				}
			}
		}

	}

?>