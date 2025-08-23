# CSV Format for Components Upload

## Overview
This document describes the CSV format required for bulk uploading components to the AVIA system.

**Important Note**: The same component (part_number) can have different IPL numbers (ipl_num) in the same manual. The system checks for exact duplicates across all fields to avoid data redundancy while allowing multiple variations of the same component.

## Required Columns

### Mandatory Fields
- **part_number** (required): The unique part number of the component
- **name** (required): The descriptive name of the component
- **ipl_num** (required): The IPL (Illustrated Parts List) number

### Optional Fields
- **assy_part_number**: Assembly part number (if applicable)
- **assy_ipl_num**: Assembly IPL number (if applicable)
- **log_card**: Log card flag (0 or 1, default: 0)
- **repair**: Repair flag (0 or 1, default: 0)
- **is_bush**: Bushing flag (0 or 1, default: 0)
- **bush_ipl_num**: Bushing IPL number (if applicable)

## CSV Format Rules

1. **File Format**: UTF-8 encoded CSV file
2. **Delimiter**: Comma (,)
3. **Headers**: First row must contain column names exactly as specified
4. **Data Types**:
   - Text fields: Any text (will be trimmed of whitespace)
   - Boolean fields (log_card, repair, is_bush): Use 0, 1, true, or false
   - Empty fields: Leave empty for optional fields

## Example CSV Content

```csv
part_number,assy_part_number,name,ipl_num,assy_ipl_num,log_card,repair,is_bush,bush_ipl_num
ABC123,ABC123-ASSY,Landing Gear Actuator,123-456,123-456A,1,0,0,
XYZ789,,Hydraulic Pump,789-012,,0,1,0,
DEF456,DEF456-REV,Shock Absorber Assembly,456-789,456-789B,1,1,1,456-789B
```

### Multiple IPL Numbers for Same Component

The same component can have different IPL numbers in the same manual:

```csv
part_number,name,ipl_num
ABC123,Landing Gear Actuator,123-456
ABC123,Landing Gear Actuator,123-457
ABC123,Landing Gear Actuator,123-458
```

**Important**: This CSV WILL work! The system allows multiple components with the same part_number in the same manual, as long as they have different ipl_num or other different data.

### Correct Approach - One Component with Multiple IPL Numbers

To represent a component with multiple IPL numbers, you should:

1. **Create one component** with the main IPL number
2. **Use the assy_ipl_num field** for additional IPL numbers
3. **Or create separate components** in different manuals

Example of valid CSV:
```csv
part_number,name,ipl_num,assy_ipl_num
ABC123,Landing Gear Actuator,123-456,123-457
XYZ789,Hydraulic Pump,789-012,789-013
```

## Validation Rules

1. **Required Fields**: part_number, name, and ipl_num cannot be empty
2. **Unique Constraint**: Exact duplicates (same part_number + manual_id + ipl_num + name + all other fields) are not allowed
3. **File Size**: Maximum 10MB
4. **File Type**: .csv or .txt files only

## Upload Process

1. Navigate to the Components page
2. Click the upload button (📤) in the Action column for the desired manual
3. Select your CSV file
4. Click "Upload Components"
5. The system will process the file and show results

## Error Handling

- **Missing Headers**: System will report which required headers are missing
- **Data Validation**: Invalid rows will be skipped and errors reported
- **Duplicate Handling**: Exact duplicate components (same data across all fields) will be skipped
- **Success Count**: Shows how many new components were added successfully

## Tips for Success

1. **Use the Template**: Download the CSV template to ensure correct format
2. **Check Data**: Verify all required fields are filled
3. **Avoid Duplicates**: The system will automatically skip existing components
4. **Test with Small Files**: Start with a few rows to test the format
5. **Backup Data**: Always backup existing data before bulk uploads
6. **Review Results**: Check the upload results for any errors or skipped duplicates

## Support

If you encounter issues with CSV uploads:
1. Check the error messages in the upload results
2. Verify your CSV format matches the requirements
3. Ensure all required fields are populated
4. Contact system administrator for technical support
