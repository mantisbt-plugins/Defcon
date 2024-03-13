
# 	Defcon plugin for Mantisbt
	
	Version 3.2.0

## Description

Defcon is a plugin for [MantisBT](http://mantisbt.org) that allows for:
1. Definition of a Default consultant per project
2. Setting the rate for each project
	a. The rate is stored @ the time of issue creation
	b. Default the program calculates an Estimated cost for fixing the issue based upon the historical rate
	c. The rate @ issue level can be adjusted
		i.	Retrieve the updates project rate
		ii.	Overwrite the default rate for this issue


This plugin was developed for a Canadian company and placed in public domain 

## Copyright

	- 2024 Cas Nuy www.NUY.info
	
## License                                                                                    

Released under the [GPL v3 license](http://opensource.org/licenses/GPL-3.0).

## Support

File bug reports and submit questions on the
[GitHub issues tracker](http://github.com/mantisbt-plugins/Defcon/issues).

## Requirements
	- MantisBT version 2.0.0


## Installation

1. Download the plugin here
2. Copy the plugin's code into a `Defcon` directory in your Mantis 
   installation's `plugins/` directory. Note that the name is case sensitive.
3. Log into Mantis as an administrator, and go to _Manage -> Manage Plugins_.
4. Locate `Defcon` in the _Available Plugins_ list, and click
   on the _Install_ link.
5. Click the hyperlink on the plugin's name to configure it.
6. In order to use the calculation of the estimate you need to configure a custm field with the name "Estimate".
	a. This field must be of type integer and represents the number of unts (hours, days) in which the rate has been defined.
7. MyView page
	you can add a section to the MyView page.<br>
	for this you need to add the following line to my_view_page.php:<br>
	event_declare('EVENT_MYVIEW');<br>
	This line needs to be inserted right after:<br>
	define( 'MY_VIEW_INC_ALLOW', true );
	
