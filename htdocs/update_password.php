<?php
	
	//��������� ������
	session_start();
	
	//��������� ���� ����������� � ��
	require("config.php");
	require("lib/lib.php");
    connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);

	if(isset($_POST["set_new_password"]) && !empty($_POST["set_new_password"])){

		//(1) ����� ��� ���������� ����� ����
		
		//���������, ���� ���������� ���������� token � ���������� ������� POST
		if(isset($_POST['token']) && !empty($_POST['token'])){
		    $token = $_POST['token'];

		}else{
			// ��������� � ������ ��������� �� ������. 
			$_SESSION["error_messages"] = "<p class='mesage_error' ><strong>������!</strong> ����������� ����������� ��� ( ���������� ������� ).</p>";
			
			//���������� ������������ �� �������� ��������� ������ ������
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
			//�������������  ������
			exit();
		}

		//���������, ���� ���������� ���������� email � ���������� ������� POST
		if(isset($_POST['email']) && !empty($_POST['email'])){
		    $email = $_POST['email'];

		}else{
			// ��������� � ������ ��������� �� ������. 
			$_SESSION["error_messages"] = "<p class='mesage_error' ><strong>������!</strong> ����������� ����� ����������� ����� ( ���������� ������� ).</p>";
			
			//���������� ������������ �� �������� ��������� ������ ������
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
			//�������������  ������
			exit();
		}

		if(isset($_POST["password"])){
		    //�������� ������� � ������ � � ����� ������
		    $password = trim($_POST["password"]);
		    //���������, ��������� �� ������
		    if(isset($_POST["confirm_password"])){
		        //�������� ������� � ������ � � ����� ������
		        $confirm_password = trim($_POST["confirm_password"]);
		        if($confirm_password != $password){
		            // ��������� � ������ ��������� �� ������. 
		            $_SESSION["error_messages"] = "<p class='mesage_error' >������ �� ���������</p>";
		            
		            //���������� ������������ �� �������� ��������� ������ ������
		            header("HTTP/1.1 301 Moved Permanently");
		            header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		            //�������������  ������
		            exit();
		        }
		    }else{
		        // ��������� � ������ ��������� �� ������. 
		        $_SESSION["error_messages"] = "<p class='mesage_error' >����������� ���� ��� ���������� ������</p>";
		        
		        //���������� ������������ �� �������� ��������� ������ ������
		        header("HTTP/1.1 301 Moved Permanently");
		        header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		        //�������������  ������
		        exit();
		    }
		    if(!empty($password)){
		        $password = htmlspecialchars($password, ENT_QUOTES);
		        //������� �������
		        //$password = md5($password."top_secret"); 
		    }else{
		        // ��������� � ������ ��������� �� ������. 
		        $_SESSION["error_messages"] = "<p class='mesage_error' >������ �� ����� ���� ������</p>";
		        
		        //���������� ������������ �� �������� ��������� ������ ������
		        header("HTTP/1.1 301 Moved Permanently");
		        header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		        //�������������  ������
		        exit();
		    }
		}else{
		    // ��������� � ������ ��������� �� ������. 
		    $_SESSION["error_messages"] = "<p class='mesage_error' >����������� ���� ��� ����� ������</p>";
		    
		    //���������� ������������ �� �������� ��������� ������ ������
		    header("HTTP/1.1 301 Moved Permanently");
		    header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		    //�������������  ������
		    exit();
		}


		global $link;
		//(2) ����� ��� ���������� ����� ����
		$query_update_password = mysqli_query($link, "UPDATE users SET password='".html_entity_decode($password)."' WHERE email='$email'");

		if(!$query_update_password){

		    // ��������� � ������ ��������� �� ������. 
		    $_SESSION["error_messages"] = "<p class='mesage_error' >�������� ������ ��� ��������� ������.</p><p><strong>�������� ������</strong>: ".mysqli_error(".$link.")."</p>";
		    
		    //���������� ������������ �� �������� ��������� ������ ������
		    header("HTTP/1.1 301 Moved Permanently");
		    header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		    
		    //�������������  ������
		    exit();

		}else{
		    //������� ��������� � ���, ��� ������ ���������� �������.
			require_once("header.php");
			echo '<h1 class="success_message text_center">������ ������� ����Σ�!</h1>';
			echo '<p class="text_center">������ �� ������ ����� � ���� �������.</p>';
			echo '<a href="'.$mywww.'"> '.$mywww.' </a>';
			require_once("footer.php");
		}

	}else{
		exit("<p><strong>������!</strong> �� ����� �� ��� �������� ��������, ������� ��� ������ ��� ���������. �� ������ ������� �� <a href=".$mywww."> ������� �������� </a>.</p>");
	}
?>