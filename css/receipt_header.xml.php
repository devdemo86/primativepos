<?php

header("Content-type: text/xml");

require("../init.php");

?>
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
				<ImageObject>
					<Name>GRAPHIC</Name>
					<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
					<BackColor Alpha="0" Red="255" Green="255" Blue="255" />
					<LinkedObjectName></LinkedObjectName>
					<Rotation>Rotation0</Rotation>
					<IsMirrored>False</IsMirrored>
					<IsVariable>False</IsVariable>
					<Image>iVBORw0KGgoAAAANSUhEUgAAAJ8AAAApAQMAAAARRmnLAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAGUExURf///////1V89WwAAAACdFJOU/8A5bcwSgAAAAlwSFlzAAAOwwAADsMBx2+oZAAAABZJREFUOMtj+I8J/jGMCo4KjgpSLAgAn3Ew0UaDB9oAAAAASUVORK5CYIIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==</Image>
					<ScaleMode>Uniform</ScaleMode>
					<BorderWidth>0</BorderWidth>
					<BorderColor Alpha="255" Red="0" Green="0" Blue="0" />
					<HorizontalAlignment>Center</HorizontalAlignment>
					<VerticalAlignment>Top</VerticalAlignment>
				</ImageObject>
				<ObjectMargin Left="0" Top="0" Right="0" Bottom="0" />
				<Length>819.026921118929</Length>
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
							<String><?php echo $pos->config->xml_header; ?>
</String>
							<Attributes>
								<Font Family="Arial" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False" />
								<ForeColor Alpha="255" Red="0" Green="0" Blue="0" />
							</Attributes>
						</Element>
					</StyledText>
				</TextObject>
				<ObjectMargin Left="0" Top="0" Right="0" Bottom="0" />
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
							<String>