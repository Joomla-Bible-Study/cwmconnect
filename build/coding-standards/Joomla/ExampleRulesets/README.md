### Selectively Applying Rules

#### NOTE: this is an exact copy of the "Selectively Applying Rules" section of the main README and is here only for reference

For consuming packages there are some items that will typically result in creating their own project ruleset.xml, rather than just directly using the Joomla ruleset.xml. A project ruleset.xml allows the coding standard to be selectivly applied for excluding 3rd party libraries, for consideration of backwards compatability in existing projects, or for adjustments necessary for projects that do not need php 5.3 compatability (which will be removed in a future version of the Joomla Coding Standard in connection of the end of php 5.3 support in all active Joomla Projects). 

For information on [selectivly applying rules read details in the PHP CodeSniffer wiki](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-ruleset.xml#selectively-applying-rules)

#### Common Rule Set Adjustments

The most common adjustment is to exclude folders with 3rd party libraries, or where the code has yet to have coding standards applied.

```xml
<!-- Exclude folders not containing production code -->
	<exclude-pattern type="relative">build/*</exclude-pattern>
	<exclude-pattern type="relative">tests/*</exclude-pattern>

	<!-- Exclude 3rd party libraries. -->
	<exclude-pattern type="relative">libraries/*</exclude-pattern>
	<exclude-pattern type="relative">vendor/*</exclude-pattern>
```

Another common adjustment is to exclude the [camelCase format requirement](http://joomla.github.io/coding-standards/?coding-standards/chapters/php.md) for "Classes, Functions, Methods, Regular Variables and Class Properties" the essentially allows for B/C with snake_case variables which were only allowed in the context of interacting with the database.

```xml
 <rule ref="Joomla">
  <exclude name="Joomla.NamingConventions.ValidFunctionName.ScopeNotCamelCaps"/>
  <exclude name="Joomla.NamingConventions.ValidVariableName.NotCamelCaps"/>
  <exclude name="Joomla.NamingConventions.ValidVariableName.MemberNotCamelCaps"/>
  <exclude name="Joomla.NamingConventions.ValidFunctionName.FunctionNoCapital"/>
 </rule>
```

Old Protected method names were at one time prefixed with an underscore. These Protected method names with underscores are depreceated in Joomla projects but for B\C reasons are still in the projects. As such excluding the MethodUnderscore sniff is a common ruleset adjustment

```xml
 <rule ref="Joomla">
  <exclude name="Joomla.NamingConventions.ValidFunctionName.MethodUnderscore"/>
  <exclude name="Joomla.NamingConventions.ValidVariableName.ClassVarHasUnderscore"/>
 </rule>
```

## Using example rulesets that Selectively Applying Rule
You have to tell you can tell PHPCS where the example ruleset folder is (i.e. install them in PHPCS)
```sh
phpcs --config-set installed_paths /path/to/joomla/coding-standards/Example-Rulesets
```
Note: the composer scripts will run when the standard is installed globally, but not when it's a dependancy. As such, you may want to run PHPCS config-set. When you run PHPCS config-set it will always overwrite the previous values. Use `--config-show` to check previous values before using `--config-set`
So instead of overwriting the existing paths you should copy the existing paths revealed with `--config-show` and add each one seperated by a comma:
`phpcs --config-set installed_paths [path_1],[path_2],[/path/to/joomla-coding-standards],[/path/to/joomla/coding-standards/Example-Rulesets]`
