<?php
	define('CAPTCHA_COOKIE', 'imgcaptcha_');
	require("lib/lib.php");
        require("config.php");
        connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);
        $mysqli='mysqli';
        
        session_start();
	//��������� ������ ��� ���������� ������, ������� ����� ���������� ��� ��������� �����.
	$_SESSION["error_messages"] = '';

	//��������� ������ ��� ���������� �������� ���������
	$_SESSION["success_messages"] = '';

	//���� ������ ������������ ���� ������
	if(isset($_POST["send"])){

		//���������, ���������� �� �����
		if(isset($_POST["captcha"])){

			//(1) ����� ��� ���������� ����� ����

		    //�������� ������� � ������ � � ����� ������
		    $captcha = trim($_POST["captcha"]);

		    if(!empty($captcha)){

		        //���������� ���������� �������� �� ��������� �� ������. 
		        if(!empty($_POST['captcha']) and md5($_POST['captcha']) != @$_COOKIE[CAPTCHA_COOKIE]){
		            
		            // ���� ����� �� �����, �� ���������� ������������ �� �������� �������������� ������, � ��� ������� ��� ��������� �� ������ ��� �� �ף� ������������ �����.
		            
		            // ��������� � ������ ��������� �� ������. 
		            $_SESSION["error_messages"] =  "<p class='mesage_error'><strong>������!</strong> �� ����� ������������ ����� </p>";
		            
		            //���������� ������������ �� �������� �������������� ������
		            header("HTTP/1.1 301 Moved Permanently");
		            header("Location: ".$mywww."/reset_password.php");
		            //������������� ������
		            exit();
		        }
		    }else{

		        // ��������� � ������ ��������� �� ������. 
		        $_SESSION["error_messages"] = "<p class='mesage_error'><strong>������!</strong> ���� ��� ����� ����� �� ������ ���� ������. </p>";

		        //���������� ������������ �� �������� �������������� ������
		        header("HTTP/1.1 301 Moved Permanently");
		        header("Location: ".$mywww."/reset_password.php");
		        //������������� ������
		        exit();
		    }
		
		    //������������ ���������� �������� �����
		    if(isset($_POST["email"])){

		        //�������� ������� � ������ � � ����� ������
		        $email = trim($_POST["email"]);

		        if(!empty($email)){

		            $email = htmlspecialchars($email, ENT_QUOTES);

		            //��������� ������ ����������� ��������� ������ � ������� ����������� ���������
		            $reg_email = "/^[a-z0-9][a-z0-9\._-]*[a-z0-9]*@([a-z0-9]+([a-z0-9-]*[a-z0-9]+)*\.)+[a-z]+/i";

		            //���� ������ ����������� ��������� ������ �� ������������� ����������� ���������
		            if( !preg_match($reg_email, $email)){

		                // ��������� � ������ ��������� �� ������. 
		                $_SESSION["error_messages"] = "<p class='mesage_error' >�� ����� ������������ email</p>";
		                
		                //���������� ������������ �� �������� �������������� ������
		                header("HTTP/1.1 301 Moved Permanently");
		                header("Location: ".$mywww."/reset_password.php");

		                //������������� ������
		                exit();
		            }
		        }else{
		            // ��������� � ������ ��������� �� ������. 
		            $_SESSION["error_messages"] = "<p class='mesage_error' > <strong>������!</strong> ���� ��� ����� ��������� ������(email) �� ������ ���� ������.</p>";
		            
		            //���������� ������������ �� �������� �������������� ������
		            header("HTTP/1.1 301 Moved Permanently");
		            header("Location: ".$mywww."/reset_password.php");
		            //������������� ������
		            exit();
		        }
		        
		    }else{
		        // ��������� � ������ ��������� �� ������. 
		        $_SESSION["error_messages"] = "<p class='mesage_error' > <strong>������!</strong> ����������� ���� ��� ����� Email</p>";
		        
		        //���������� ������������ �� �������� �������������� ������
		        header("HTTP/1.1 301 Moved Permanently");
		        header("Location: ".$mywww."/reset_password.php");

		        //������������� ������
		        exit();
		    }

		    // (2) ����� ��� ����������� ������� � ��
			global $link;
		    //������ � �� �� ������� ������������.
		    $result_query_select = mysqli_query($link, "SELECT active FROM `users` WHERE email = '".$email."'");

		    if(!$result_query_select){

		        // ��������� � ������ ��������� �� ������. 
		        $_SESSION["error_messages"] = "<p class='mesage_error' > ������ ������� �� ������� ������������ �� ��</p>";
		        
		        //���������� ������������ �� �������� �������������� ������
		        header("HTTP/1.1 301 Moved Permanently");
		        header("Location: ".$mywww."/reset_password.php");

		        //������������� ������
		        exit();

		    }else{

		        //���������, ���� � ���� ��� ������������ � ������ �������, �� ������� ��������� �� ������
		        if($result_query_select->num_rows == 1){

		        	//���������, ����������� �� ��������� email
		        	while(($row = $result_query_select->fetch_assoc()) !=false){
		        	    
		        	    // (3) ����� ��� ���������� ����� ����

		        	    //���� email �� ��������ģ�
		        	    if((int)$row["active"] === 0){

		        	    	// ��������� � ������ ��������� �� ������. 
		        	    	$_SESSION["error_messages"] = "<p class='mesage_error' ><strong>������!</strong> �� �� ������ ������������ ���� ������, ������ ��� ��������� ����� ����������� ����� ($email) �� ��������ģ�. </p><p> ������ ��� �� ��� email �������� ���������� ������ � ������� �� ���������.</p>";
		        	    	$result=mysqli_query($link, "select confirm,point,name from `users` WHERE email = '".$email."'");
		        	    	if($result->num_rows == 1){
		        	    	  $row2 = mysqli_fetch_array($result);
                                          $confirm = $row2['confirm'];
                                          $point = $row2['point'];
                                          $name = $row2['name'];
		        	    	}
  mail ($email, "$mywww: activation", "Hello, $name!.

Congratulations! Your Fidonet address: $mynode.$point

To activate your account on $mywww, please click link below:

$mywww/activation.php?key=$confirm&point=$point


", 'From: '.$adminmail);

		        	    	//���������� ������������ �� �������� �������������� ������
		        	    	header("HTTP/1.1 301 Moved Permanently");
		        	    	header("Location: ".$mywww."/reset_password.php");

		        	    	//������������� ������
		        	    	exit();

		        	    }else{
		        	    	//���������� ������������� � ���������� token
		        	    	$token=md5($email.time());

		        	    	//��������� ����� � ��
					$query_update_token = mysqli_query($link, "UPDATE users SET confirm='$token' WHERE email='$email'");

		        	    	if(!$query_update_token){

		        	    	    // ��������� � ������ ��������� �� ������. 
		        	    	    $_SESSION["error_messages"] = "<p class='mesage_error' >������ ���������� ������</p><p><strong>�������� ������</strong>: ".$mysqli->error."</p>";
		        	    	    
		        	    	    //���������� ������������ �� �������� �������������� ������
		        	    	    header("HTTP/1.1 301 Moved Permanently");
		        	    	    header("Location: ".$mywww."/reset_password.php");

		        	    	    //�������������  ������
		        	    	    exit();

		        	    	}else{
								$res=mysqli_query($link, "select point from `users` WHERE email = '".$email."'");
								if (!$res)
									die(mysqli_error($link));
									$point = $res->fetch_row()[0];
									$res->close();
			        	    	//���������� ������ �� �������� ��������� ������ ������.
			        	    	$link_reset_password = $mywww."/set_new_password.php?email=$email&token=$token";
			        	    //	var_dump($email);
			        	    //	var_dump($token);

					    //	die(print_r($link_reset_password, true ));
	                             //���������� ��������� ������
	                             $subject = "�������������� ������ �� ����� ".$_SERVER['HTTP_HOST'];

	                             //������������� ��������� ��������� ������ � �������� ���
	                             $subject = "=?koi8-r?B?".base64_encode($subject)."?=";

	                             //���������� ���� ���������
	                             $message = '������������! <br/> <br/> ��� �������� �����: '.$point.'<br /> ��� �������������� ������ �� ����� <a href="http://'.$mywww.'"> '.$mywww.' </a>, ��������� �� ���� <a href="'.$link_reset_password.'">������</a>.';
	                             
	                             //���������� �������������� ��������� ��� ��������� ������� mail.ru
	                             //���������� $email_admin, ��������� � ����� dbconnect.php
	                             $headers = "FROM: $adminmail\r\nReply-to: $adminmail\r\nContent-type: text/html; charset=koi8-r\r\n";
	                             
	                             //���������� ��������� � ������� �� �������� ��������� ������ ������ � ��������� ���������� �� ��� ������� ��� ���. 
	                             if(mail($email, $subject, $message, $headers)){

	                                 $_SESSION["success_messages"] = "<p class='success_message' >������ �� �������� ��������� ������ ������ ������, ���� ���������� �� ��������� E-mail ($email) </p>";

	                                 //���������� ������������ �� �������� �������������� ������ � ������� ����� ��� ����� email
	                                 header("HTTP/1.1 301 Moved Permanently");
	                                 header("Location: ".$mywww."/reset_password.php?hidden_form=1");

	                                 exit();

	                             }else{
	                                 $_SESSION["error_messages"] = "<p class='mesage_error' >������ ��� ����������� ������ �� ����� ".$email.", � ������ �� �������� �������������� ������. </p>";

	                                 //���������� ������������ �� �������� �������������� ������
	                                 header("HTTP/1.1 301 Moved Permanently");
	                                 header("Location: ".$mywww."/reset_password.php");
	                                 
	                                 //������������� ������
	                                 exit();
	                             }
                             }
                        } // if($row["email_status"] === 0)

		        	} // End while

		        }else{
		            
		            // ��������� � ������ ��������� �� ������. 
		            $_SESSION["error_messages"] = "<p class='mesage_error' ><strong>������!</strong> ����� ������������ �� ���������������</p>";
		            
		            //���������� ������������ �� �������� �������������� ������
		            header("HTTP/1.1 301 Moved Permanently");
		            header("Location: ".$mywww."/reset_password.php");

		            //������������� ������
		            exit();
		        }
		    }

		}else{ //if(isset($_POST["captcha"]))
            //���� ����� �� ��������
            exit("<p><strong>������!</strong> ����������� ����������� ���, �� ���� ��� �����. �� ������ ������� �� <a href=".$mywww."> ������� �������� </a>.</p>");
        }
	}else{ //if(isset($_POST["send"]))
        exit("<p><strong>������!</strong> �� ����� �� ��� �������� ��������, ������� ��� ������ ��� ���������. �� ������ ������� �� <a href=".$mywww."/> ������� �������� </a>.</p>");
    }
?>