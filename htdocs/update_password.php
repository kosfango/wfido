<?php
	
	//Запускаем сессию
	session_start();
	
	//Добавляем файл подключения к БД
	require("config.php");
	require("lib/lib.php");
    connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);

	if(isset($_POST["set_new_password"]) && !empty($_POST["set_new_password"])){

		//(1) Место для следующего куска кода
		
		//Проверяем, если существует переменная token в глобальном массиве POST
		if(isset($_POST['token']) && !empty($_POST['token'])){
		    $token = $_POST['token'];

		}else{
			// Сохраняем в сессию сообщение об ошибке. 
			$_SESSION["error_messages"] = "<p class='mesage_error' ><strong>Ошибка!</strong> Отсутствует проверочный код ( Передаётся скрытно ).</p>";
			
			//Возвращаем пользователя на страницу установки нового пароля
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
			//Останавливаем  скрипт
			exit();
		}

		//Проверяем, если существует переменная email в глобальном массиве POST
		if(isset($_POST['email']) && !empty($_POST['email'])){
		    $email = $_POST['email'];

		}else{
			// Сохраняем в сессию сообщение об ошибке. 
			$_SESSION["error_messages"] = "<p class='mesage_error' ><strong>Ошибка!</strong> Отсутствует адрес электронной почты ( Передаётся скрытно ).</p>";
			
			//Возвращаем пользователя на страницу установки нового пароля
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
			//Останавливаем  скрипт
			exit();
		}

		if(isset($_POST["password"])){
		    //Обрезаем пробелы с начала и с конца строки
		    $password = trim($_POST["password"]);
		    //Проверяем, совпадают ли пароли
		    if(isset($_POST["confirm_password"])){
		        //Обрезаем пробелы с начала и с конца строки
		        $confirm_password = trim($_POST["confirm_password"]);
		        if($confirm_password != $password){
		            // Сохраняем в сессию сообщение об ошибке. 
		            $_SESSION["error_messages"] = "<p class='mesage_error' >Пароли не совпадают</p>";
		            
		            //Возвращаем пользователя на страницу установки нового пароля
		            header("HTTP/1.1 301 Moved Permanently");
		            header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		            //Останавливаем  скрипт
		            exit();
		        }
		    }else{
		        // Сохраняем в сессию сообщение об ошибке. 
		        $_SESSION["error_messages"] = "<p class='mesage_error' >Отсутствует поле для повторения пароля</p>";
		        
		        //Возвращаем пользователя на страницу установки нового пароля
		        header("HTTP/1.1 301 Moved Permanently");
		        header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		        //Останавливаем  скрипт
		        exit();
		    }
		    if(!empty($password)){
		        $password = htmlspecialchars($password, ENT_QUOTES);
		        //Шифруем папроль
		        //$password = md5($password."top_secret"); 
		    }else{
		        // Сохраняем в сессию сообщение об ошибке. 
		        $_SESSION["error_messages"] = "<p class='mesage_error' >Пароль не может быть пустым</p>";
		        
		        //Возвращаем пользователя на страницу установки нового пароля
		        header("HTTP/1.1 301 Moved Permanently");
		        header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		        //Останавливаем  скрипт
		        exit();
		    }
		}else{
		    // Сохраняем в сессию сообщение об ошибке. 
		    $_SESSION["error_messages"] = "<p class='mesage_error' >Отсутствует поле для ввода пароля</p>";
		    
		    //Возвращаем пользователя на страницу установки нового пароля
		    header("HTTP/1.1 301 Moved Permanently");
		    header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		    //Останавливаем  скрипт
		    exit();
		}


		global $link;
		//(2) Место для следующего куска кода
		$query_update_password = mysqli_query($link, "UPDATE users SET password='".html_entity_decode($password)."' WHERE email='$email'");

		if(!$query_update_password){

		    // Сохраняем в сессию сообщение об ошибке. 
		    $_SESSION["error_messages"] = "<p class='mesage_error' >Возникла ошибка при изменении пароля.</p><p><strong>Описание ошибки</strong>: ".mysqli_error(".$link.")."</p>";
		    
		    //Возвращаем пользователя на страницу установки нового пароля
		    header("HTTP/1.1 301 Moved Permanently");
		    header("Location: ".$mywww."/set_new_password.php?email=$email&token=$token");
		    
		    //Останавливаем  скрипт
		    exit();

		}else{
		    //Выводим сообщение о том, что пароль установлен успешно.
			require_once("header.php");
			echo '<h1 class="success_message text_center">Пароль успешно изменён!</h1>';
			echo '<p class="text_center">Теперь Вы можете войти в свой аккаунт.</p>';
			echo '<a href="'.$mywww.'"> '.$mywww.' </a>';
			require_once("footer.php");
		}

	}else{
		exit("<p><strong>Ошибка!</strong> Вы зашли на эту страницу напрямую, поэтому нет данных для обработки. Вы можете перейти на <a href=".$mywww."> главную страницу </a>.</p>");
	}
?>