<?xml version="1.0" encoding="utf-8"?>
<ContinuousLabel Version="8.0" Units="twips">
	<PaperOrientation>Portrait</PaperOrientation>
	<Id>Continuous</Id>
	<PaperName>30270 Continuous</PaperName>
	<LengthMode>Auto</LengthMode>
	<LabelLength>4046.4</LabelLength>
	<RootCell>
		<Length>4046.4</Length>
		<LengthMode>Auto</LengthMode>
		<BorderWidth>0</BorderWidth>
		<BorderStyle>Solid</BorderStyle>
		<BorderColor Alpha="255" Red="0" Green="0" Blue="0" />
		<SubcellsOrientation>Vertical</SubcellsOrientation>
		<Subcells>
		<Cell>
				<TextObject>
					<Name>Text_00</Name>
					<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
					<BackColor Alpha="0" Red="255" Green="255" Blue="255" />
					<LinkedObjectName></LinkedObjectName>
					<Rotation>Rotation0</Rotation>
					<IsMirrored>False</IsMirrored>
					<IsVariable>True</IsVariable>
					<HorizontalAlignment>Center</HorizontalAlignment>
					<VerticalAlignment>Top</VerticalAlignment>
					<TextFitMode>None</TextFitMode>
					<UseFullFontHeight>True</UseFullFontHeight>
					<Verticalized>False</Verticalized>
					<StyledText>
						<Element>
							<String><?php echo $pos->config->xml_header; ?></String>
							<Attributes>
								<Font Family="Arial" Size="12" Bold="False" Italic="False" Underline="False" Strikeout="False" />
								<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
							</Attributes>
						</Element>
					</StyledText>
				</TextObject>
				<ObjectMargin Left="0" Top="0" Right="0" Bottom="0" />
				<Length>864</Length>
				<LengthMode>Fixed</LengthMode>
				<BorderWidth>0</BorderWidth>
				<BorderStyle>Solid</BorderStyle>
				<BorderColor Alpha="255" Red="0" Green="0" Blue="0" />
			</Cell>
			<Cell>
				<TextObject>
					<Name>Text</Name>
					<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
					<BackColor Alpha="0" Red="255" Green="255" Blue="255" />
					<LinkedObjectName></LinkedObjectName>
					<Rotation>Rotation0</Rotation>
					<IsMirrored>False</IsMirrored>
					<IsVariable>True</IsVariable>
					<HorizontalAlignment>Center</HorizontalAlignment>
					<VerticalAlignment>Top</VerticalAlignment>
					<TextFitMode>None</TextFitMode>
					<UseFullFontHeight>True</UseFullFontHeight>
					<Verticalized>False</Verticalized>
					<StyledText>
						<Element>
						<?php
						
						if($reportType == 'open_balance')
							echo "<String>Opening Balance\n</String>\n";
						else
						{
						?>
							<String>End of Day Report
</String>
					<?php
					}
					?>
							<Attributes>
								<Font Family="Arial" Size="10" Bold="False" Italic="False" Underline="False" Strikeout="False" />
								<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
							</Attributes>
						</Element>
						<Element>
							<String><?php echo date("m/d/Y g:i a"); ?></String>
							<Attributes>
								<Font Family="Arial" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False" />
								<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
							</Attributes>
						</Element>
					</StyledText>
				</TextObject>
				<ObjectMargin Left="0" Top="72" Right="0" Bottom="0" />
				<Length>720</Length>
				<LengthMode>Fixed</LengthMode>
				<BorderWidth>0</BorderWidth>
				<BorderStyle>Solid</BorderStyle>
				<BorderColor Alpha="255" Red="0" Green="0" Blue="0" />
			</Cell>
			<Cell>
				<TextObject>
					<Name>TEXT_1</Name>
					<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
					<BackColor Alpha="0" Red="255" Green="255" Blue="255" />
					<LinkedObjectName></LinkedObjectName>
					<Rotation>Rotation0</Rotation>
					<IsMirrored>False</IsMirrored>
					<IsVariable>True</IsVariable>
					<HorizontalAlignment>Left</HorizontalAlignment>
					<VerticalAlignment>Top</VerticalAlignment>
					<TextFitMode>None</TextFitMode>
					<UseFullFontHeight>True</UseFullFontHeight>
					<Verticalized>False</Verticalized>
					<StyledText>
						<Element>