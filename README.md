# Enhanced Tag List Field

## Overview

Based on Symphony's built-in tag list field, the enhanced tag list provides four additional (optional) features:

1. Suggestion threshold. A numerical value specifying the minimum number of times a tag must be used before it will appear in the suggestion list. Handy for keeping the interface tidier if you've got lots and lots of tags, but need only the most frequent ones in your suggestion list.

2. Ordering. Symphony's built-in tag list reorders tags on submission. Enhanced Tag List allows you the option of preserving the order in which you entered your tags, and provides an "order" attribute in the XML output.

3. Custom delimiters. Allows you to specify an alternative delimiter of up to 5 characters. Useful if your tags need to be able to contain commas, for instance.

4. Suggestions from external XML.

## Installation

1. Upload the `/enhancedtaglist` folder to your Symphony `/extensions` folder.

2. Enable it by selecting the "Field: Enhanced Tag List", choose Enable from the with-selected menu, then click Apply. You can now add the "Enhanced Tag List" field to your sections.

## Usage

1. Suggestion Threshold - Leave blank for no threshold. Otherwise enter any integer.

2. Ordering - When adding the field to your section, check the "Preserve list order" box. Then simply enter tag list items in the desired order. You can reorder at any time by rearranging the input and saving the entry.

3. Delimiters - Enter a delimiter of up to five characters (e.g. ";" "::" "+" etc). You can change your delimiter at any time for any field without having to reenter data.

4. External XML Suggestions - Provide URL of external XML source, and XPath for selecting suggestion elements.
