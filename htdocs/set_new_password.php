<?php 
//��������� ���� ����������� � ��
require("lib/lib.php");
require("config.php");
connect_to_sql($sql_host,$sql_base,$sql_user,$sql_pass);


//���������, ���� ���������� ���������� token � ���������� ������� GET
if(isset($_GET['token']) && !empty($_GET['token'])){
    $token = $_GET['token'];
}else{
    exit("<p><strong>������!</strong> ����������� ����������� ���.</p>");
}

//���������, ���� ���������� ���������� email � ���������� ������� GET
if(isset($_GET['email']) && !empty($_GET['email'])){
    $email = $_GET['email'];
}else{
    exit("<p><strong>������!</strong> ����������� ����� ����������� �����.</p>");
}

global $link;
//������ ������ �� ������� ������ �� ������� confirm_users
$query_select_user = mysqli_query($link, "SELECT confirm FROM `users` WHERE `email` = '".$email."'");
//���� ������ � ������� ���
if(($row = $query_select_user->fetch_assoc()) != false){
    
    //���� ����� ������������ ����������
    if($query_select_user->num_rows == 1){
        //��������� ��������� �� token
        if($token == $row['confirm']){


require_once("header.php");
?>
            
            <!-- ��� JavaScript -->
            <script type="text/javascript">
                $(document).ready(function(){
                    "use strict";
                    //================ ��������� ������� ==================
                    var password = $('input[name=password]');
                    var confirm_password = $('input[name=confirm_password]');
                    
                    password.blur(function(){
                        if(password.val() != ''){
                            //���� ����� ���ģ����� ������ ������ ����� ��������, �� ������� ��������� �� ������
                            if(password.val().length < 4){
                                //������� ��������� �� ������
                                $('#valid_password_message').text('����������� ����� ������ 6 ��������');
                                //���������, ���� ������ �� ���������, �� ������� ��������� �� ������
                                if(password.val() !== confirm_password.val()){
                                    //������� ��������� �� ������
                                    $('#valid_confirm_password_message').text('������ �� ���������');
                                }
                                // ������������� ������ ��������
                                $('input[type=submit]').attr('disabled', true);
                                
                            }else{
                                //�����, ���� ����� ������� ������ ������ ����� ��������, �� �� ����� ���������, ���� ���  ���������. 
                                if(password.val() !== confirm_password.val()){
                                    //������� ��������� �� ������
                                    $('#valid_confirm_password_message').text('������ �� ���������');
                                    // ������������� ������ ��������
                                    $('input[type=submit]').attr('disabled', true);
                                }else{
                                    // ������� ��������� �� ������ � ���� ��� ����� ���������� ������
                                    $('#valid_confirm_password_message').text('');
                                    //���������� ������ ��������
                                    $('input[type=submit]').attr('disabled', false);
                                }
                                // ������� ��������� �� ������ � ���� ��� ����� ������
                                $('#valid_password_message').text('');
                            }
                        }else{
                            $('#valid_password_message').text('������� ������');
                        }
                    });

                    confirm_password.blur(function(){
                        //���� ������ �� ���������
                        if(password.val() !== confirm_password.val()){
                            //������� ��������� �� ������
                            $('#valid_confirm_password_message').text('������ �� ���������');
                            // ������������� ������ ��������
                            $('input[type=submit]').attr('disabled', true);
                        }else{
                            //�����, ��������� ����� ������
                            if(password.val().length > 4){
                                // ������� ��������� �� ������ � ���� ��� ����� ������
                                $('#valid_password_message').text('');
                                //���������� ������ ��������
                                $('input[type=submit]').attr('disabled', false);
                            }
                            // ������� ��������� �� ������ � ���� ��� ����� ���������� ������
                            $('#valid_confirm_password_message').text('');
                        }
                    });
                });
            </script>

            <div class="center_block">
                
                <h2>��������� ������ ������</h2>

                <!-- ����� ��������� ������ ������ -->
                <form action="update_password.php" method="post">
                    <table>
                        <tr>
                            <td> ������� ����� ������: </td>
                            <td>
                                <input type="password" name="password" placeholder="������� 6 ��������" required="required" /><br />
                                <span id="valid_password_message" class="mesage_error"></span>
                            </td>
                        </tr>
                        <tr>
                            <td> ��������� ������: </td>
                            <td>
                                <input type="password" name="confirm_password" placeholder="������� 6 ��������" required="required" /><br />
                                <span id="valid_confirm_password_message" class="mesage_error"></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="hidden" name="token" value="<?=$token?>">
                                <input type="hidden" name="email" value="<?=$email?>">
                                <input type="submit" name="set_new_password" value="�������� ������" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
<?php

//Подключение подвала
            require_once("footer.php");

        }else{
            exit("<p><strong>������!</strong> ������������ ����������� ���.</p>");
        }
    }else{
        exit("<p><strong>������!</strong> ����� ������������ �� ��������������� </p>");
    }
}else{
    //�����, ���� ���� ������ � ������� � ��
    exit("<p><strong>������!</strong> ���� ��� ������ ������������ �� ��. </p>");
}
// ���������� ������� ������ ������������ �� ������� users
$query_select_user->close();
//��������� ����������� � ��
mysqli_close();

require_once("footer.php");

?>