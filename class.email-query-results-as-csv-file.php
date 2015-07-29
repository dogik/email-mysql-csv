<?php
/*
Email MySQL Query Results as a CSV File Attachment
Author:  Stephen R. Owens  (www.Studio-Owens.com)
Version: 2.2 [2:41 AM Saturday, February 22, 2014]

Sends an email with a CSV file attachment that contains the results of a MySQL query.
Copyright (C) 2009-2014 Stephen R. Owens

LICENSE:
This software is licensed under the GNU GPL version 3.0 or later.
http://www.gnu.org/licenses/gpl-3.0-standalone.html

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'vendor/autoload.php';

class EmailQueryResultsAsCsv {

	# MySQL Server
	private $mySQL_server = '';
	private $mySQL_server_port = '3306';

	# MySQL Database Name
	private $mySQL_database = '';

	# MySQL Username
	private $mySQL_user = '';

	# MySQL Password
	private $mySQL_password = '';

	# MySQL Query
	# something like "SELECT * FROM table_name"
	private $mySQL_query = '';

	# CSV File Name
	# filename for the attached file, use something like "mysql_results.csv"
	private $csv_file_name = '';

	# Multiple File Data array
	# File Name & reuseable elements + mySQL_query
	private $arr_file_data = array();

	# CSV file reuseable elements
	private $csv_contain = '';
	private $csv_separate = '';
	private $csv_end_row = '';


	private $smtp_host = '';
	private $smtp_port = '587';
	private $smtp_security = 'tls';
	private $smtp_username = '';
	private $smtp_password = '';

	/**
	 * @param string $smtp_host
	 */
	public function setSmtpHost($smtp_host)
	{
		$this->smtp_host = $smtp_host;
	}

	/**
	 * @param string $smtp_port
	 */
	public function setSmtpPort($smtp_port)
	{
		$this->smtp_port = $smtp_port;
	}

	/**
	 * @param string $smtp_security
	 */
	public function setSmtpSecurity($smtp_security)
	{
		$this->smtp_security = $smtp_security;
	}

	/**
	 * @param string $smtp_username
	 */
	public function setSmtpUsername($smtp_username)
	{
		$this->smtp_username = $smtp_username;
	}

	/**
	 * @param string $smtp_password
	 */
	public function setSmtpPassword($smtp_password)
	{
		$this->smtp_password = $smtp_password;
	}

	# Email Message
	# This is an HTML formatted message
	private $email_html_msg = "<h1>MySQL Query Results as CSV Attachment</h1>
  <p>This attachment can be opened with OpenOffice.org Calc, Google Docs, or Microsoft Excel.</p>";

	# used to output success messages to the screen
	private $debugFlag = false;

	# --------------------------------------
	#  Methods
	# --------------------------------------

	# constructor
	public function __construct($s, $d, $u, $p)
	{
		$this->setDBinfo($s, $d, $u, $p);
		$this->setCSVinfo();
		$this->setCSVname();
	}

	# destructor
	public function __destruct()
	{

	}

	public function setDBinfo($s, $d, $u, $p)
	{
		$this->mySQL_server = $s;
		$this->mySQL_database = $d;
		$this->mySQL_user = $u;
		$this->mySQL_password = $p;
	}

	public function setDBinfoServerPort($p)
	{
		$this->mySQL_server_port = $p;
	}

	public function setQuery($sql)
	{
		$this->mySQL_query = $sql;
	}

	public function setEmailMessage($msg)
	{
		$this->email_html_msg = $msg;
	}

	public function setCSVname($fn = "mysql_results.csv")
	{
		$this->csv_file_name = $fn;
	}

	public function setCSVinfo($c = '"', $s = ",", $er = "\n")
	{
		$this->csv_contain = $c;
		$this->csv_separate = $s;
		$this->csv_end_row = $er;
	}

	public function setMultiFile($fn, $sql)
	{
		$this->arr_file_data[] = array("csv_file_name" => $fn, "mySQL_query" => $sql, "csv_contain" => $this->csv_contain, "csv_separate" => $this->csv_separate, "csv_end_row" => $this->csv_end_row);
	}

	public function debugMode($bool)
	{
		$this->debugFlag = $bool;
	}

	public function sendEmail($email_from, $email_to, $email_subject, $email_body, $filename)
	{
		# check to see if the array for file info and queries has data if not add the single file data
		if (!isset($this->arr_file_data[0]["csv_file_name"]))
		{
			$this->arr_file_data[0] = array("csv_file_name" => $this->csv_file_name, "mySQL_query" => $this->mySQL_query, "csv_contain" => $this->csv_contain, "csv_separate" => $this->csv_separate, "csv_end_row" => $this->csv_end_row);
		}

		# --------------------------------------
		#   CONNECT TO MYSQL DATABASE
		$mysqli = new mysqli($this->mySQL_server, $this->mySQL_user, $this->mySQL_password, $this->mySQL_database, $this->mySQL_server_port);
		if ($mysqli->connect_errno)
		{
			die('ERROR: Could not connect to MySQL server: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
		}
		if ($this->debugFlag)
		{
			echo "Step 1: Connected to MySQL server successfully. \n\n";
		}

		foreach ($this->arr_file_data AS $file_data)
		{
			$csv_file = $this->buildCSV($file_data, $mysqli);
		}

		file_put_contents($filename, $csv_file);

		$mail = new PHPMailer;

		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = $this->smtp_host;  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = $this->smtp_username;                 // SMTP username
		$mail->Password = $this->smtp_password;                           // SMTP password
		$mail->SMTPSecure = $this->smtp_security;                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $this->smtp_port;                                    // TCP port to connect to

		$mail->From = $email_from;
		$mail->FromName = 'Mailer';

		if (!is_array($email_to))
		{
			$email_to = [$email_to];
		}

		foreach ($email_to as $to)
		{
			$mail->addAddress($to);
		}

		$mail->addReplyTo($email_from);

		$mail->addAttachment($filename);         // Add attachments
		$mail->isHTML(true);                                  // Set email format to HTML

		$mail->Subject = $email_subject;
		$mail->Body = $email_body;

		if (!$mail->send())
		{
			echo 'Message could not be sent.';
			echo 'Mailer Error: ' . $mail->ErrorInfo;
		}
		else
		{
			echo 'Message has been sent';
		}

		# reset the attachment array so the object can be used anew
		$this->arr_file_data = array();

		if ($this->debugFlag)
		{
			echo "FINISHED.";
		}
	}

	private function buildCSV($file_data, $mysqli)
	{
		# container to hold the CSV file as it's built
		$csv_file = "";

		# run the MySQL query and check to see if results were returned
		$result = $mysqli->query($file_data["mySQL_query"]);
		if (!$result)
		{
			die("ERROR: Invalid query \n MySQL error: (" . $mysqli->errno . ")" . $mysqli->error . "\n Your query: " . $this->mySQL_query);
		}

		# only return a non blank data set with query returns at least one record
		if ($result->num_rows > 0)
		{
			if ($this->debugFlag)
			{
				echo "Step 2 (repeats for each attachment): MySQL query ran successfully. \n\n";
			}

			# store the number of columns and field data from the results
			$columns = $mysqli->field_count;
			$column_data = $result->fetch_fields();

			# Build a header row using the mysql field names
			$header_row = '';
			$i = 0;
			foreach ($column_data as $col)
			{
				//for ($i = 0; $i < $columns; $i++) {
				$column_title = $file_data["csv_contain"] . stripslashes($col->name) . $file_data["csv_contain"];
				$column_title .= ($i < $columns - 1) ? $file_data["csv_separate"] : ''; #the last column does not have the column separator
				$header_row .= $column_title;
				$i++;
			}
			$csv_file .= $header_row . $file_data["csv_end_row"]; # add header row to CSV file

			# Build the data rows by walking through the results array one row at a time
			$data_rows = '';
			while ($row = $result->fetch_array(MYSQLI_NUM))
			{
				for ($i = 0; $i < $columns; $i++)
				{
					# clean up the data; strip slashes; replace double quotes with two single quotes
					$data_rows .= $file_data["csv_contain"] . preg_replace('/' . $file_data["csv_contain"] . '/', $file_data["csv_contain"] . $file_data["csv_contain"], stripslashes($row[$i])) . $file_data["csv_contain"];
					$data_rows .= ($i < $columns - 1) ? $file_data["csv_separate"] : '';
				}
				$data_rows .= $this->csv_end_row; # add data row to CSV file
			}
			$csv_file .= $data_rows; # add the data rows to CSV file

			if ($this->debugFlag)
			{
				echo "Step 3 (repeats for each attachment): CSV file built. \n\n";
			}
		}
		else
		{
			echo "Step 2 (repeats for each attachment): MySQL query ran successfully \n\n";
			echo "Step 3 (repeats for each attachment): NO results were returned for this query. No file will be sent for the following query: \n " . $this->mySQL_query . " \n\n";
		}

		# Return the completed file
		return $csv_file;
	}
}

?>
