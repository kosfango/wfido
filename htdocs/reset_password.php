<?php
    //п÷п╬п╢п╨п╩я▌я┤п╣п╫п╦п╣ я┬п╟п©п╨п╦
    require_once("header.php");
?>
<script type="text/javascript">
    $(document).ready(function(){
        "use strict";
        //регулярное выражение для проверки email
        var pattern = /^[a-z0-9][a-z0-9\._-]*[a-z0-9]*@([a-z0-9]+([a-z0-9-]*[a-z0-9]+)*\.)+[a-z]+/i;
        var mail = $('input[name=email]');
        
        mail.blur(function(){
            if(mail.val() != ''){
                // Проверяем, если email соответствует регулярному выражению
                if(mail.val().search(pattern) == 0){
                    // Убираем сообщение об ошибке
                    $('#valid_email_message').text('');
                    //Активируем кнопку отправки
                    $('input[type=submit]').attr('disabled', false);
                }else{
                    //Выводим сообщение об ошибке
                    $('#valid_email_message').text('Не правильный Email');
                    // Дезактивируем кнопку отправки
                    $('input[type=submit]').attr('disabled', true);
                }
            }else{
                $('#valid_email_message').text('Введите Ваш email');
            }
        });
    });
</script>

<!-- Блок для вывода сообщений -->
<div class="block_for_messages">
    <?php

        if(isset($_SESSION["error_messages"]) && !empty($_SESSION["error_messages"])){
            echo $_SESSION["error_messages"];
             //Уничтожаем ячейку error_messages, чтобы сообщения об ошибках не появились заново при обновлении страницы
            unset($_SESSION["error_messages"]);
        }
        if(isset($_SESSION["success_messages"]) && !empty($_SESSION["success_messages"])){
            echo $_SESSION["success_messages"];
            
            //Уничтожаем ячейку success_messages,  чтобы сообщения не появились заново при обновлении страницы
            unset($_SESSION["success_messages"]);
        }
    ?>
</div>

<?php 
    //Проверяем, если пользователь не авторизован, то выводим форму регистрации, 
    //иначе выводим сообщение о том, что он уже зарегистрирован
    if((!isset($_SESSION["email"]) && !isset($_SESSION["password"]))) {
        if(!isset($_GET["hidden_form"])){
?>
            <div class="center_block">
                <h2>Восстановление пароля</h2>
                
                <!-- Абзац -->
                <p class="text_center mesage_error" id="valid_email_message"></p>
                <form action="send_link_reset_password.php" method="post" name="form_request_email" >
            		<table>
            			<tr>
            				<td> Введите Ваш <br />E-mail: </td>
            				<td>
            					<input type="email" name="email" placeholder="" >
            				</td>
            			</tr>
            			<tr>
            			    <td> Введите капчу: </td>
            			    <td>
            			        <p>
            			            <img title="Щелкните для изменения кода" alt="Captcha" src="jcaptcha.php" style="border: 1px solid black" onclick="this.src='jcaptcha.php?id=' + (+new Date());" /> <br />
            			            <input type="text" name="captcha" placeholder="Проверочный код" />
            			        </p>
            			    </td>
            			</tr>
            			<tr>
            				<td colspan="2" class="text_center">
            					<input type="submit" name="send" value="Восстановить">
            				</td>
            			</tr>
            		</table>
                </form>
            </div>

<?php 
    	}//закрываем условие hidden_form

    }else{
?>
        <div id="authorized">
            <h2>Вы уже авторизованы</h2>
        </div>
<?php
}
?>
<?php
    //п÷п╬п╢п╨п╩я▌я┤п╣п╫п╦п╣ я┬п╟п©п╨п╦
    require_once("footer.php");
?>


