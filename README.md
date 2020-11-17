#MyQuiz Api Documentation

###### This is the api that uses MyQuiz app.

##### Host domain

`https://my-quiz-api.herokuapp.com/`

##### All request urls must be like following.
	 Hostname/ + Endpoint

#### Eg:
- `https://my-quiz-api.herokuapp.com/api/login`

- `https://my-quiz-api.herokuapp.com/api/register`


## End points


#### Register

#####Endpoint 
`/api/register`

##### Body
```json
{
    "email": "wilber81@example.net",
    "firstname": "Firstname",
    "lastname": "Lastname",
    "password": "123456789",
    "account_type": "INSTRUCTOR"
}
```
##### Success response
```json
{
  "logged": true,
  "accessToken": "Mhgjerdogtytkvdoforetorpogpdofpskdwmlemretrf...",
  "account_type"  "INSTRUCTOR"
  "email":  "wilber81@example.net",
  "firstname": "Firstname",
  "lastname": "Lastname",
}
```

##### Validation error response
```json
{
    "email": "Invalid username or password.",
	"password": "Passwords dont match.",
}
```

##### Note
###### After successfull register, save the accessToken field for future use.

#### Login

#####Endpoint 
`/api/login`

##### Body
```json
{
    "email": "wilber81@example.net",
    "password": "password"
}
```
##### Success response
```json
{
    "logged": true,
    "accessToken": "Mhgjerdogtytkvdoforetorpogpdofpskdwmlemretrf...",
    "firstname" : "First name",
    "lastname" : "Last name",
    "account_type" : "INSTRUCTOR",
    "email" : "wilber81@example.net",
}
```

##### Validation error response
```json
{
    "email": "You have entered an invalid username or password"
}
```

##### Note
###### After successfull login, save the accessToken field for future use.


#### Instructors Account

#### get Exams

#####Endpoint 
`/api/instructor/exams`

##### Body
```json
{
   "current_page": 1,
   "per_page": 5
}
```
##### Success response
```json
{
   "exams": [
   		{
			"id": 0,
            "title" "This is the exam title",
            "duration": "02:20",
            "question_count": {
				"mcq": 10,
			}
            "student_enrolled": 10,
		},
		{
			"id": 0,
            "title" "This is the exam title",
            "duration": "02:20",
            "question_count": {
				"mcq": 10,
			}
            "student_enrolled": 10,
		},
		{
			"id": 0,
            "title" "This is the exam title",
            "duration": "02:20",
            "question_count": {
				"mcq": 10,
			}
            "student_enrolled": 10,
		},
   ],
   "paginator": {
		   "total_exams": 8,
			"current_page": 2,
			"total_pages": 2,
			"is_next_page_exists": true,
			"is_prev_page_exists": true,
		 },
}
```


#### Create new exam

#####Endpoint 
`/api/instructor/exams/new-exam`

##### Body
```json
{
    "title": "Test exam-{{$randomCity}}",
    "subject": 1,
    "duration": "00:30",
    "questions": {
        "mcq": [
            {
                "question": "This is question?",
                "answers": [
                  { "answer":"this is answer.", "is_correct":1 },
                  { "answer":"this is answer.", "is_correct":0 },
				  { "answer":"this is answer.", "is_correct":0 },
				  { "answer":"this is answer.", "is_correct":0 }
                ]
            },{
                "question": "This is question?",
                "answers": [
                  { "answer":"this is answer.", "is_correct":1 },
                  { "answer":"this is answer.", "is_correct":0 },
				  { "answer":"this is answer.", "is_correct":0 },
				  { "answer":"this is answer.", "is_correct":0 }
                ]
            }
		]
	}
}
```
##### Success response
```json
{
		"subject_id": 38,
		"duration": "01:45",
		"title": "This is title"
}
```