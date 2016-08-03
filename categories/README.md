API to create and get Joomla categories

## Create / Update Category

```http
POST /index.php?option=com_api&app=categories&resource=categories&format=raw
```
OR update an existing category
```http
POST /index.php?option=com_api&app=categories&resource=categories&format=raw&id=:id
```

#### Request Params

| Param Name | Required | Type | Comment  |
| ---------- | -------- | ------- | :---- |
| title      | YES      | STRING |         |
| alias      | NO      | STRING | URL alias. Will be generated based on title if kept empty |
| description    | NO      | STRING |        |
| published    | NO      | INT | 1 = Published (Default) / 0 = Unpublished |
| parent_id      | NO      | INT |  Specify a parent category id if you wish to create a subcategory |
| language | NO | STRING | Will use the site's default language if none provided. |
| extension | YES | STRING | Since Joomla supports using the categories extension for 3rd party components, this field specifies the extension name. Use com_content for article categories.  |
| access | NO | INT | Access Level for Category |


#### Response Params

| Param Name | Comment |
| ---------- | :------ |
| success | true if the category was created, false if there was a problem |
| message | Error mesage in case success is false |
| data.results | Array containing a single [Category Object](#category-object) in case of success. Empty array in case of failure. |

## Get Categories List
```http
GET /index.php?option=com_api&app=categories&resource=categories&format=raw
```
#### Request Params

| Param Name | Required | Comment |
| ---------- | -------- | :------ |
| limit         | NO       | Defaults to 20        | 
| limitstart      | NO      | Defaults to 0        |
| filters | NO | Key value pairs of values to filter on |
| search | NO | search key for searching category titles |
| fields         | NO       | Defaults to id, title, created_time | 


#### Response Params

| Param Name | Comment |
| ---------- | :------- |
| success | true if the API call succeeds, false if there was a problem |
| message | Error mesage in case success is false |
| data.results | Array of [Category Objects](#category-object) in case of success. Empty array in case of failure. |


## Get Single Category
```http
GET /index.php?option=com_api&app=categories&resource=categories&format=raw&id=:id
```

#### Request Params

| Param Name | Required | Comment |
| ---------- | -------- | :------ |
| fields         | NO       | Defaults to id, title, created_time | 


#### Response Params

| Param Name | Comment  |
| ---------- | :------- |
| success | true if the API call succeeds, false if there was a problem |
| message | Error mesage in case success is false |
| data.results | Array containing a single [Category Object](#category-object) in case of success. Empty array in case of failure. |


## Category Object
The actual contents of the category object will vary based on which fields are requested, however the below is the list of all possible fields.

```json
{
  "id" : "",
  "title" : "",
  "alias" : "",
  "description" : "",
  "published" : "",
  "created_time" : "",
  "modified_time" : "",
  "access" : "",
  "language" : "",
  "created_user_id": {
    "id" : "",
    "name" : "",
    "avatar" : ""
  },
  "language" : ""
}
```