<?php
    //Подключение шапки
    require_once("header.php");
?>
<script type="text/javascript">
    $(document).ready(function(){
        "use strict";
        //���������� ��������� ��� �������� email
        var pattern = /^[a-z0-9][a-z0-9\._-]*[a-z0-9]*@([a-z0-9]+([a-z0-9-]*[a-z0-9]+)*\.)+[a-z]+/i;
        var mail = $('input[name=email]');
        
        mail.blur(function(){
            if(mail.val() != ''){
                // ���������, ���� email ������������� ����������� ���������
                if(mail.val().search(pattern) == 0){
                    // ������� ��������� �� ������
                    $('#valid_email_message').text('');
                    //���������� ������ ��������
                    $('input[type=submit]').attr('disabled', false);
                }else{
                    //������� ��������� �� ������
                    $('#valid_email_message').text('�� ���������� Email');
                    // ������������� ������ ��������
                    $('input[type=submit]').attr('disabled', true);
                }
            }else{
                $('#valid_email_message').text('������� ��� email');
            }
        });
    });
</script>

<!-- ���� ��� ������ ��������� -->
<div class="block_for_messages">
    <?php

        if(isset($_SESSION["error_messages"]) && !empty($_SESSION["error_messages"])){
            echo $_SESSION["error_messages"];
             //���������� ������ error_messages, ����� ��������� �� ������� �� ��������� ������ ��� ���������� ��������
            unset($_SESSION["error_messages"]);
        }
        if(isset($_SESSION["success_messages"]) && !empty($_SESSION["success_messages"])){
            echo $_SESSION["success_messages"];
            
            //���������� ������ success_messages,  ����� ��������� �� ��������� ������ ��� ���������� ��������
            unset($_SESSION["success_messages"]);
        }
    ?>
</div>

<?php 
    //���������, ���� ������������ �� �����������, �� ������� ����� �����������, 
    //����� ������� ��������� � ���, ��� �� ��� ���������������
    if((!isset($_SESSION["email"]) && !isset($_SESSION["password"]))) {
        if(!isset($_GET["hidden_form"])){
?>
            <div class="center_block">
                <h2>�������������� ������</h2>
                
                <!-- ����� -->
                <p class="text_center mesage_error" id="valid_email_message"></p>
                <form action="send_link_reset_password.php" method="post" name="form_request_email" >
            		<table>
            			<tr>
            				<td> ������� ��� <br />E-mail: </td>
            				<td>
            					<input type="email" name="email" placeholder="" >
            				</td>
            			</tr>
            			<tr>
            			    <td> ������� �����: </td>
            			    <td>
            			        <p>
            			            <img title="�������� ��� ��������� ����" alt="Captcha" src="jcaptcha.php" style="border: 1px solid black" onclick="this.src='jcaptcha.php?id=' + (+new Date());" /> <br />
            			            <input type="text" name="captcha" placeholder="����������� ���" />
            			        </p>
            			    </td>
            			</tr>
            			<tr>
            				<td colspan="2" class="text_center">
            					<input type="submit" name="send" value="������������">
            				</td>
            			</tr>
            		</table>
                </form>
            </div>

<?php 
    	}//��������� ������� hidden_form

    }else{
?>
        <div id="authorized">
            <h2>�� ��� ������������</h2>
        </div>
<?php
}
?>
<?php
    //Подключение шапки
    require_once("footer.php");
?>


