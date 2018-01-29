<?php
require_once ('../.function.php');
$connect = mysqli_connect("$db_host", "$db_user", "$db_pass", "$db_name");
$output = '';
if(isset($_POST["export"]))
{
 $query = "SELECT * FROM agents WHERE NOT md_ciruser = '0'"; 
 $result = mysqli_query($connect, $query);
 if(mysqli_num_rows($result) > 0)
 {
  $output .= '
   <table class="table" bordered="1">  
                    <tr>  
			<th>Agentname</th>
			<th>Missions</th> 
			<th>Check-in Time</th> 
			<th>CIR-ID</th> 
 
                    </tr>
  ';
  while($row = mysqli_fetch_array($result))
  {
   $output .= '    
	<tr> <td>'.$row["agentname"].'</td>
		 <td>'.$row["md_check"].'</td>
		 <td>'.$row["md_time"].'</td>
		 <td>'.$row["md_ciruser"].'</td>

       </tr>				
   ';
  }
  $output .= '</table>';
  header('Content-Type: application/xls');
  header('Content-Disposition: attachment; filename=cir_usera_cheched-in.xls');
  echo $output;
 }
}
?>