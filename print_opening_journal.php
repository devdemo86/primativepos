<?php

require("init.php");


//
// print out the closing report
//


if($_GET['printLabel'] == '1')
{
/*
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=report.label");

	$reportType = 'open_balance';

	require("css/report_header.php");
*/
}
// look up the tickets for today

$open_amount = number_format($_GET['amount'], 2);

$item_lines = '';

$item_length = 750 + 159; // have to specify size of this to printer


//if($_GET['printLabel'] == '1')
//	echo "	<String>\r\n";


//$total_checks = number_format($total_checks, 2);


// no printer just output to the screen
$today_date = date("n/d/Y g:i a");

if($_GET['printLabel'] == '0')
{

$noPrinterDoc = <<<END

$today_date
Open     $open_amount

END;

echo $noPrinterDoc;

exit;

}
/*
$doc = <<<END
</String>
							<Attributes>
								<Font Family="Courier New" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False" />
								<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
							</Attributes>
						</Element>
					</StyledText>
				</TextObject>
				<ObjectMargin Left="72" Top="150" Right="0" Bottom="150" />
				<Length>$item_length</Length>
				<LengthMode>Auto</LengthMode>
				<BorderWidth>0</BorderWidth>
				<BorderStyle>Solid</BorderStyle>
				<BorderColor Alpha="255" Red="0" Green="0" Blue="0" />
			</Cell>
			<Cell>
				<TextObject>
					<Name>TEXT</Name>
					<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
					<BackColor Alpha="0" Red="255" Green="255" Blue="255" />
					<LinkedObjectName></LinkedObjectName>
					<Rotation>Rotation0</Rotation>
					<IsMirrored>False</IsMirrored>
					<IsVariable>False</IsVariable>
					<HorizontalAlignment>Left</HorizontalAlignment>
					<VerticalAlignment>Top</VerticalAlignment>
					<TextFitMode>None</TextFitMode>
					<UseFullFontHeight>True</UseFullFontHeight>
					<Verticalized>False</Verticalized>
					<StyledText>
						<Element>
							<String>
*/
$doc = "     $today_date\n\n";
$doc .= " Open     $open_amount";
/*
</String>
							<Attributes>
								<Font Family="Arial" Size="9" Bold="False" Italic="False" Underline="False" Strikeout="False" />
								<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
							</Attributes>
						</Element>
					</StyledText>
				</TextObject>
				<ObjectMargin Left="0" Top="150" Right="0" Bottom="150" />
				<Length>1684.97307888107</Length>
				<LengthMode>Fixed</LengthMode>
				<BorderWidth>0</BorderWidth>
				<BorderStyle>Solid</BorderStyle>
				<BorderColor Alpha="255" Red="0" Green="0" Blue="0" />
			</Cell>
		</Subcells>
	</RootCell>
</ContinuousLabel>

END;

echo $doc;
*/

    $fp = fopen($pos->config->tmp_dir . "/open_journal.txt", "w");
    fwrite($fp, $doc);
    echo $output;
    fclose($fp);

    system("lpr -P Dymo-LabelWriter-450 -o ContinuousPaper=1 " . $pos->config->tmp_dir . "/open_journal.txt");

echo $doc;
?>
